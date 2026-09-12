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
  "toUserId": "A8LSIWVLGZ1Q",
  "amount": 50.00,
  "idempotencyKey": "tx_abc123def456"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "transaction": {
      "id": "TXN_a1b2c3d4e5f6",
      "accountId": "ACC_XK9mP2vL",
      "idempotencyKey": "tx_abc123def456",
      "relatedUserId": "A8LSIWVLGZ1Q",
      "type": "SPEND",
      "amount": 50.00,
      "description": "Transfer to A8LSIWVLGZ1Q",
      "createdAt": "2026-09-07T10:30:00Z"
    },
    "newBalance": 292.50
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
        "id": "TXN_a1b2c3d4e5f6",
        "accountId": "ACC_XK9mP2vL",
        "idempotencyKey": "initial_income_TFJOTJQEL4P3",
        "relatedUserId": null,
        "type": "INCOME",
        "amount": 342.50,
        "description": "Saldo inicial de bienvenida",
        "createdAt": "2026-09-07T10:00:00Z"
      }
    ],
    "count": 1
  }
}
```

## Tipos de Transacción
- `INCOME`: Ingreso/Abono (incluye el saldo inicial)
- `SPEND`: Egreso/Transferencia enviada
- `REQUEST`: Petición de pago (sin implementación de resolución en esta fase)

## Reglas de Negocio
- **Idempotencia**: Si se envía la misma `idempotencyKey`, retorna la transacción existente sin crear duplicados
- **No negativos**: El balance nunca puede ser negativo
- **No infinitos/NaN**: Se validan valores numéricos antes de procesar
- **Transfer**: El transfer solo crea SPEND en el sender. El receiver recibe un REQUEST (pendiente approval). NO se crea INCOME automático para el destinatario.

## Modelo de Datos

### Transaction
```typescript
{
  id: string                    // VARCHAR(16), "TXN_{nanoid}"
  accountId: string             // VARCHAR(16), FK a Account
  type: 'INCOME' | 'SPEND' | 'REQUEST'
  amount: number               // DECIMAL(15,2)
  idempotencyKey: string        // VARCHAR(64), único
  relatedUserId: string | null // VARCHAR(12)
  description: string | null   // VARCHAR(255)
  createdAt: Date
}
```

## Índices

| Índice | Columna | Tipo |
|--------|---------|------|
| idx_transactions_account | accountId | INDEX |
| idx_transactions_type | type | INDEX |
| idx_transactions_idem | idempotencyKey | UNIQUE |
| idx_transactions_created | createdAt | INDEX |
