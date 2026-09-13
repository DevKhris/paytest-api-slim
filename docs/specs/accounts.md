# Account API Spec

## Endpoints

### GET /accounts/balance
Obtiene el balance actual de la cuenta del usuario autenticado.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "user_unique_id": "TFJOTJQEL4P3",
    "balance": 342.50
  }
}
```

**Errores:**
- `401`: No autorizado (token inválido o expirado)
- `404`: Cuenta no encontrada

## Reglas de Negocio
- El balance se calcula dinámicamente desde el historial de transacciones
- Formula: `balance = SUM(INCOME) - SUM(SPEND)`
- No hay valores negativos posibles (validación en capa de servicio)