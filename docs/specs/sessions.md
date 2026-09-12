# Session API Spec

## Endpoints

### GET /sessions/info
Obtiene información de la sesión actual.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "ses_abc123def456",
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "userId": "TFJOTJQEL4P3",
    "ipAddress": "192.168.1.100",
    "userAgent": "Mozilla/5.0...",
    "status": "ACTIVE",
    "createdAt": "2026-09-07T10:00:00Z",
    "expiresAt": "2026-09-07T11:00:00Z"
  }
}
```

**Errores:**
- `401`: Sesión no encontrada o expirada

---

### POST /sessions/logout
Invalida el token de sesión actual.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "message": "Logged out successfully"
  }
}
```

## Reglas de Negocio
- El token JWT tiene vigencia de 1 hora (configurable via JWT_EXPIRES_IN)
- IP y User-Agent se almacenan para auditoría
- Sesiones expiradas son limpiadas automáticamente
- El token sigue formato Bearer para autenticación estándar
- El status puede ser `ACTIVE` o `EXPIRED`

## Modelo de Sesión

```typescript
{
  id: string              // VARCHAR(36), UUID
  token: string           // VARCHAR(512)
  userId: string          // VARCHAR(12), FK a User
  ipAddress: string       // VARCHAR(45)
  userAgent: string       // VARCHAR(512)
  status: 'ACTIVE' | 'EXPIRED'
  createdAt: Date
  expiresAt: Date
}
```

## Índices

| Índice | Columna | Tipo |
|--------|---------|------|
| idx_sessions_token | token | UNIQUE |
| idx_sessions_user | userId | INDEX |
| idx_sessions_expires | expiresAt | INDEX |
