# Transaction API Spec

## Endpoints

### POST /transactions/send
Envía dinero a otro usuario.

**Headers:**
```
Authorization: Bearer <token>
```

**Request:**
```json
{
  "to_user_unique_id": "A8LSIWVLGZ1Q",
  "amount": 50.00,
  "idempotency_key": "tx_abc123def456"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "transaction": {
      "id": 1,
      "idempotency_key": "tx_abc123def456",
      "account_user_unique_id": "TFJOTJQEL4P3",
      "counterpart_user_unique_id": "A8LSIWVLGZ1Q",
      "type": "SPEND",
      "amount": 50.00,
      "description": "Transfer to A8LSIWVLGZ1Q",
      "created_at": "2026-09-07T10:30:00Z"
    },
    "new_balance": 292.50
  }
}
```

**Errores:**
- `400`: Fondos insuficientes, monto inválido, o transferencia a sí mismo
- `404`: Usuario destinatario no encontrado
- `422`: Validación de datos fallida

---

### GET /transactions/history
Obtiene el historial de transacciones.

**Headers:**
```
Authorization: Bearer <token>
```

**Query Parameters:**
- `limit` (opcional): Número de transacciones a retornar (default: 50, max: 100)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "id": 2,
        "idempotency_key": "initial_income_TFJOTJQEL4P3",
        "account_user_unique_id": "TFJOTJQEL4P3",
        "counterpart_user_unique_id": null,
        "type": "INCOME",
        "amount": 342.50,
        "description": "Saldo inicial de bienvenida",
        "created_at": "2026-09-07T10:00:00Z"
      }
    ],
    "count": 1
  }
}
```

## Reglas de Negocio
- **Idempotencia**: Si se envía la misma `idempotency_key`, retorna la transacción existente
- **No negativos**: El balance nunca puede ser negativo
- **No infinitos/NaN**: Se validan valores numéricos
- **Auto-ingreso**: El destinatario recibe automáticamente una transacción INCOME