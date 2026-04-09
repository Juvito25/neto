# NETO Project - Quick Start Guide

## Project State

**NETO** is a SaaS B2B platform that connects AI chatbots to WhatsApp for small businesses. The project is partially built with:

- ✅ Authentication (Laravel Sanctum tokens)
- ✅ Multi-tenant system with proper data isolation
- ✅ Catalog feature (products/services upload via CSV/JSON)
- ✅ Chatbot integration with Groq API
- ⏳ Plan limits + Setup Wizard (to build)

## Quick Reference

### Start the project
```bash
cd /home/juan/Proyectos/Neto
docker-compose up -d
# Access at http://localhost
```

### API Testing
```bash
# Login
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password"}'

# Protected endpoints (use the token)
curl -X GET http://localhost/api/tenant \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Key Files
- Backend: `/home/juan/Proyectos/Neto/backend/`
- Frontend: `/home/juan/Proyectos/Neto/frontend/`
- API Routes: `backend/routes/api.php`
- Vue Views: `frontend/src/views/`

## Current Tasks to Continue

The next phase involves:

1. **Plan Limits**: Add `plans` table with max catalog items (500/2000/10000)
2. **Onboarding Wizard**: Guided 5-step setup flow for new users
3. **Validation**: Enforce plan limits when uploading catalogs

## Documentation

See `/home/juan/Proyectos/Neto/PROMPT_NETO_Limites_y_Wizard.md` for detailed implementation instructions.