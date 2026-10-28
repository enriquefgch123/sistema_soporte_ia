import time
import os
import joblib
from fastapi import FastAPI
from pydantic import BaseModel

app = FastAPI(title="Motor de IA - Soporte Técnico", version="1.0")

# Rutas de los archivos .pkl generados por reentrenar.py
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MODELO_PATH = os.path.join(BASE_DIR, 'modelo_categoria.pkl')
VECTORIZADOR_PATH = os.path.join(BASE_DIR, 'vectorizador.pkl')

# Cargar el modelo entrenado y el vectorizador desde el disco
if os.path.exists(MODELO_PATH) and os.path.exists(VECTORIZADOR_PATH):
    model_categoria = joblib.load(MODELO_PATH)
    vectorizador = joblib.load(VECTORIZADOR_PATH)
else:
    raise RuntimeError("No se encontraron los archivos del modelo. Ejecuta reentrenar.py primero.")

# Esquema de datos para recibir la petición HTTP POST
class TicketInput(BaseModel):
    descripcion: str

@app.post("/predecir")
def predecir_incidencia(ticket: TicketInput):
    inicio = time.time()
    
    texto = [ticket.descripcion]
    
    # 1. Transformar el texto usando el vectorizador entrenado
    texto_vectorizado = vectorizador.transform(texto)
    
    # 2. Predecir la categoría utilizando el modelo real cargado
    categoria_predicha = model_categoria.predict(texto_vectorizado)[0]
    
    # Para la prioridad (puedes mantener una regla o dejarla estática/dinámica según tu avance)
    prioridad_predicha = "Media" 
    
    fin = time.time()
    tiempo_proceso = round(fin - inicio, 4) # Medido en segundos
    
    return {
        "descripcion": ticket.descripcion,
        "categoria_sugerida": categoria_predicha,
        "prioridad_sugerida": prioridad_predicha,
        "tiempo_procesamiento_ia": tiempo_proceso
    }