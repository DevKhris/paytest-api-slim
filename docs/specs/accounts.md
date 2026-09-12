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
    "accountId": "ACC_XK9mP2vL",
    "userId": "TFJOTJQEL4P3",
    "balance": 342.50,
    "currency": "USD",
    "createdAt": "2026-09-07T10:00:00Z",
    "updatedAt": "2026-09-07T10:30:00Z"
  }
}
```

**Errores:**
- `401`: No autorizado (token inválido o expirado)
- `404`: Cuenta no encontrada

## Reglas de Negocio
- El balance se calcula dinámicamente desde el historial de transacciones
- Fórmula: `balance = Σ(INCOME amounts) - Σ(SPEND amounts) - Σ(REQUEST amounts)`
- No hay valores negativos posibles (validación en capa de servicio)
- Una cuenta está vinculada a un único usuario mediante su ID único
- El balance NO se almacena en la tabla accounts

## Modelo de Datos

### Account
```typescript
{
  id: string           // VARCHAR(16), formato "ACC_{nanoid}"
  userId: string       // VARCHAR(12), FK a User, único
  createdAt: Date      // Timestamp
  updatedAt: Date      // Timestamp
}
```
