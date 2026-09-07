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
    "user_unique_id": "TFJOTJQEL4P3",
    "ip_address": "192.168.1.100",
    "user_agent": "Mozilla/5.0...",
    "created_at": "2026-09-07T10:00:00Z",
    "expires_at": "2026-09-07T11:00:00Z"
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
