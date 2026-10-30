"""
Hotel Luna Azul - Exportación a modelo NO RELACIONAL (documentos JSON)
=====================================================================

La base relacional (MySQL) guarda huéspedes, habitaciones y reservas en
tablas separadas unidas por llaves foráneas. Este script toma esos datos
y los convierte en DOCUMENTOS, que es como los almacenaría una base NoSQL
documental (por ejemplo MongoDB): cada reserva es un documento que lleva
"embebidos" los datos del huésped y de la habitación, sin necesidad de JOIN.

Además lee la bitácora de auditoría (storage/bitacora.jsonl) que la
aplicación PHP escribe en formato JSON Lines y genera un reporte.

Salidas (carpeta storage/nosql/):
  - reservas_documentos.json   colección de documentos de reservas
  - huespedes_documentos.json  colección de huéspedes con su historial embebido
  - reporte_ocupacion.json     agregados calculados (tipo "aggregation pipeline")

Si se define la variable MONGO_URI y está instalado pymongo, también
inserta las colecciones en MongoDB.

Uso:
    pip install pymysql            (y opcionalmente: pip install pymongo)
    python exportar_nosql.py
"""
import json
import os
import sys
from collections import Counter, defaultdict
from datetime import date, datetime
from decimal import Decimal
from pathlib import Path

try:
    import pymysql
except ImportError:
    sys.exit("Falta la librería pymysql. Instálela con:  pip install pymysql")

RAIZ = Path(__file__).resolve().parent.parent
SALIDA = RAIZ / "storage" / "nosql"
BITACORA = RAIZ / "storage" / "bitacora.jsonl"

CONFIG = {
    "host": os.getenv("DB_HOST", "127.0.0.1"),
    "port": int(os.getenv("DB_PORT", "3306")),
    "user": os.getenv("DB_USER", "root"),
    "password": os.getenv("DB_PASS", ""),
    "database": os.getenv("DB_NAME", "hotel_luna_azul"),
    "charset": "utf8mb4",
    "cursorclass": pymysql.cursors.DictCursor,
}


def a_json(valor):
    """Convierte tipos de MySQL (fechas, decimales) a tipos JSON."""
    if isinstance(valor, (date, datetime)):
        return valor.isoformat()
    if isinstance(valor, Decimal):
        return float(valor)
    return valor


def limpiar(fila):
    return {k: a_json(v) for k, v in fila.items()}


def leer_mysql():
    sql = """
        SELECT r.id, r.codigo, r.fecha_ingreso, r.fecha_salida, r.num_huespedes,
               r.precio_noche, r.valor_total, r.estado, r.observaciones, r.created_at,
               h.id AS h_id, h.nombre_completo, h.tipo_documento, h.numero_documento,
               h.celular, h.correo, h.edad, h.contacto_emergencia, h.parentesco_emergencia,
               hab.numero AS hab_numero, hab.piso, t.nombre AS tipo, t.capacidad
        FROM reservas r
        JOIN huespedes h ON h.id = r.huesped_id
        JOIN habitaciones hab ON hab.id = r.habitacion_id
        JOIN tipos_habitacion t ON t.id = hab.tipo_id
        ORDER BY r.fecha_ingreso
    """
    conexion = pymysql.connect(**CONFIG)
    try:
        with conexion.cursor() as cur:
            cur.execute(sql)
            reservas = [limpiar(f) for f in cur.fetchall()]
            cur.execute("SELECT * FROM huespedes ORDER BY nombre_completo")
            huespedes = [limpiar(f) for f in cur.fetchall()]
            cur.execute("SELECT COUNT(*) AS n FROM habitaciones WHERE estado = 'activa'")
            activas = cur.fetchone()["n"]
    finally:
        conexion.close()
    return reservas, huespedes, activas


def construir_documentos(reservas, huespedes):
    """Desnormaliza: cada reserva lleva embebidos huésped y habitación."""
    docs_reservas = []
    historial = defaultdict(list)

    for r in reservas:
        noches = (date.fromisoformat(r["fecha_salida"]) - date.fromisoformat(r["fecha_ingreso"])).days
        doc = {
            "_id": r["codigo"],
            "estado": r["estado"],
            "estadia": {
                "check_in": r["fecha_ingreso"],
                "check_out": r["fecha_salida"],
                "noches": noches,
                "personas": r["num_huespedes"],
            },
            "huesped": {
                "id": r["h_id"],
                "nombre": r["nombre_completo"],
                "documento": {"tipo": r["tipo_documento"], "numero": r["numero_documento"]},
                "contacto": {"celular": r["celular"], "correo": r["correo"]},
                "emergencia": {"nombre": r["contacto_emergencia"], "parentesco": r["parentesco_emergencia"]},
            },
            "habitacion": {"numero": r["hab_numero"], "piso": r["piso"], "tipo": r["tipo"], "capacidad": r["capacidad"]},
            "valor": {"tarifa_noche": r["precio_noche"], "total": r["valor_total"], "moneda": "COP"},
            "creada": r["created_at"],
        }
        if r["observaciones"]:
            doc["observaciones"] = r["observaciones"]   # campo opcional: solo existe si hay dato
        docs_reservas.append(doc)
        historial[r["h_id"]].append({"codigo": r["codigo"], "habitacion": r["hab_numero"],
                                     "check_in": r["fecha_ingreso"], "estado": r["estado"]})

    docs_huespedes = []
    for h in huespedes:
        docs_huespedes.append({
            "_id": f'{h["tipo_documento"]}-{h["numero_documento"]}',
            "nombre": h["nombre_completo"],
            "edad": h["edad"],
            "contacto": {"telefono": h["telefono"], "celular": h["celular"],
                         "correo": h["correo"], "direccion": h["direccion"]},
            "emergencia": {"nombre": h["contacto_emergencia"], "parentesco": h["parentesco_emergencia"]},
            "reservas": historial.get(h["id"], []),
        })
    return docs_reservas, docs_huespedes


def construir_reporte(docs_reservas, activas):
    """Agregados equivalentes a un aggregation pipeline de MongoDB."""
    confirmadas = [d for d in docs_reservas if d["estado"] in ("confirmada", "finalizada")]
    por_tipo = defaultdict(lambda: {"reservas": 0, "noches": 0, "ingresos": 0.0})
    for d in confirmadas:
        t = por_tipo[d["habitacion"]["tipo"]]
        t["reservas"] += 1
        t["noches"] += d["estadia"]["noches"]
        t["ingresos"] += d["valor"]["total"]

    hoy = date.today().isoformat()
    ocupadas_hoy = sum(1 for d in docs_reservas if d["estado"] == "confirmada"
                       and d["estadia"]["check_in"] <= hoy < d["estadia"]["check_out"])

    acciones = Counter()
    if BITACORA.exists():
        with BITACORA.open(encoding="utf-8") as f:
            for linea in f:
                try:
                    evento = json.loads(linea)
                    acciones[f'{evento["entidad"]}:{evento["accion"]}'] += 1
                except (json.JSONDecodeError, KeyError):
                    continue

    return {
        "generado": datetime.now().isoformat(timespec="seconds"),
        "total_reservas": len(docs_reservas),
        "por_estado": dict(Counter(d["estado"] for d in docs_reservas)),
        "ocupacion_hoy": {"ocupadas": ocupadas_hoy, "activas": activas,
                          "porcentaje": round(ocupadas_hoy * 100 / activas, 1) if activas else 0},
        "por_tipo_habitacion": dict(por_tipo),
        "estadia_promedio_noches": round(sum(d["estadia"]["noches"] for d in confirmadas) / len(confirmadas), 2) if confirmadas else 0,
        "eventos_bitacora": dict(acciones),
    }


def guardar(nombre, datos):
    SALIDA.mkdir(parents=True, exist_ok=True)
    ruta = SALIDA / nombre
    ruta.write_text(json.dumps(datos, ensure_ascii=False, indent=2), encoding="utf-8")
    return ruta


def enviar_a_mongo(docs_reservas, docs_huespedes):
    uri = os.getenv("MONGO_URI")
    if not uri:
        return "MongoDB omitido (defina MONGO_URI para enviar las colecciones)."
    try:
        from pymongo import MongoClient
    except ImportError:
        return "MongoDB omitido: instale pymongo."
    bd = MongoClient(uri)["hotel_luna_azul"]
    for nombre, docs in (("reservas", docs_reservas), ("huespedes", docs_huespedes)):
        bd[nombre].delete_many({})
        if docs:
            bd[nombre].insert_many(docs)
    return f"MongoDB: {len(docs_reservas)} reservas y {len(docs_huespedes)} huéspedes insertados."


def main():
    print("Leyendo base relacional MySQL…")
    try:
        reservas, huespedes, activas = leer_mysql()
    except pymysql.MySQLError as e:
        sys.exit(f"No se pudo conectar a MySQL: {e}")

    docs_reservas, docs_huespedes = construir_documentos(reservas, huespedes)
    reporte = construir_reporte(docs_reservas, activas)

    print("Colecciones generadas:")
    print("  ", guardar("reservas_documentos.json", docs_reservas))
    print("  ", guardar("huespedes_documentos.json", docs_huespedes))
    print("  ", guardar("reporte_ocupacion.json", reporte))
    print(enviar_a_mongo(docs_reservas, docs_huespedes))
    print()
    print(f"Reservas: {reporte['total_reservas']}  |  Por estado: {reporte['por_estado']}")
    o = reporte["ocupacion_hoy"]
    print(f"Ocupación hoy: {o['ocupadas']} de {o['activas']} habitaciones ({o['porcentaje']} %)")


if __name__ == "__main__":
    main()
