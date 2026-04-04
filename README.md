# NETO

> SaaS B2B para automatización de WhatsApp con IA para pequeños negocios argentinos

## ¿Qué es NETO?

NETO es un chatbot de WhatsApp con IA que permite a kioscos, ferreterías, peluquerías, restaurantes y otros pequeños negocios automatizar la atención al cliente sin conocimiento técnico.

## Características

- 🤖 **IA Automática**: Claude Haiku responde consultas automáticamente
- 📱 **WhatsApp**: Conecta tu número sin perder el acceso
- 📦 **Gestión de Productos**: Carga tu inventario y el bot busca automáticamente
- 💬 **Historial de Conversaciones**: Revisá todas las conversaciones
- 📊 **Métricas**: Seguimiento de mensajes y tasa de respuesta
- 🔌 **Multi-plan**: Desde 500 mensajes/mes hasta ilimitado

## Tech Stack

| Capa | Tecnología |
|------|------------|
| Backend | Laravel 11 (API REST) |
| Frontend | Vue 3 + Pinia + Vite |
| Base de datos | PostgreSQL + pgvector |
| Colas | Laravel Queues + Redis |
| WhatsApp | Evolution API |
| LLM | Anthropic Claude Haiku |
| Embeddings | OpenAI text-embedding-3-small |

##快速启动

### Prerrequisitos

- Docker + Docker Compose
- Git

### Instalación

```bash
# Clonar el repositorio
git clone https://github.com/Juvito25/neto.git
cd neto

# Copiar configuración
cp .env.example .env

# Levantar contenedores
docker-compose up -d

# Generar key de Laravel
docker-compose exec app php artisan key:generate

# Ejecutar migraciones
docker-compose exec app php artisan migrate
```

### Servicios

| Servicio | URL |
|----------|-----|
| Frontend | http://localhost:5173 |
| API | http://localhost/api |
| Evolution API | http://localhost:8080 |

## Configuración

### Variables de entorno

```env
# APIs de IA
ANTHROPIC_API_KEY=sk-ant-...
OPENAI_API_KEY=sk-proj-...

# Evolution API
EVOLUTION_API_KEY=tu_clave

# Base de datos
DB_PASSWORD=secret
REDIS_PASSWORD=redis_secret
```

### Planes

| Plan | Precio | Mensajes/mes |
|------|--------|---------------|
| Starter | $12 USD | 500 |
| Pro | $28 USD | 2,000 |
| Business | $55 USD | Ilimitado |

Trial de 14 días gratis en todos los planes.

## Arquitectura

```
WhatsApp → Evolution API → Webhook → Queue → ProcessIncomingMessageJob
                                                      ↓
                                              Claude Haiku
                                                      ↓
                                              Evolution API → WhatsApp
```

## Desarrollo

```bash
# Ver logs
docker-compose logs -f

# Reiniciar worker
docker-compose restart worker

# Acceder al contenedor
docker-compose exec app bash
```

## Licencia

MIT
