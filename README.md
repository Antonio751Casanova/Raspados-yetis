## Equipo de Trabajo y Roles Scrum (Ciclo Agosto – Noviembre 2026)



| Integrante | Rol | Story Points |

| :--- | :--- | :---: |

| **Juan Antonio Casanova Torres** (Integrante 1) | Tech Lead, Arquitectura y Calidad Técnica | 23 SP |

| **José Manuel García Vega** (Integrante 2) | Scrum Master, Project Manager y Frontend | 24 SP |

| **Víctor Jonathan Montoya Luna** (Integrante 3) | Finanzas, Riesgos, Sostenibilidad y QA | 21 SP |



### Alcance Definitivo (Rama `main`)

**Módulos activos y validados:** Dashboard administrativo, Insumos a granel (`ml/g/pzas`), Productos y Recetas, Motor de Costeo y Cotizador con exportación PDF (`RY-XXXX`), Historial de Cotizaciones, Agenda de Eventos (Split-View), Directorio de Clientes (CRM) y Notificaciones persistentes.
**Fuera de alcance (Fase 2 futura):** Módulo E de Recursos Humanos (`modulo-e-recursos-humanos`), apps móviles nativas, facturación CFDI/SAT y despliegue en servidor comercial de pago.


### Resumen de Viabilidad Financiera y Sostenibilidad

**Evaluación financiera (3 años, TREMA 15%):** Inversión inicial `$72,699 MXN` (incluye 10% contingencia), VAN `$64,063 MXN`, TIR `61.9%`, ROI acumulado `149.0%`, Payback simple `1.28 años` y relación B/C `1.71` (viable en escenarios Conservador, Base y Optimista).
**Sostenibilidad:** Reducción estimada de 85–95% en papelería (PDF), 40–60% en merma de insumos y criterios de accesibilidad WCAG.


### Guía Rápida de Inicio Local

Clonar el repositorio y levantar el backend Laravel (`composer install`, configurar `.env`, `php artisan migrate --seed`, `php artisan serve`).
Levantar el frontend Astro en `frontend/` (`npm install` y `npm run dev`) y abrir `http://localhost:4321`.
