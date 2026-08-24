# Apollo Weather System

## ✅ **FREE API Integration - OpenMeteo**

Sistema de clima ao vivo para Rio de Janeiro usando **OpenMeteo** (100% gratuito, sem API key).

---

## 📡 **Como Funciona**

### **1. API Source**
- **Provider:** [OpenMeteo](https://open-meteo.com/)
- **Endpoint:** `https://api.open-meteo.com/v1/forecast`
- **Location:** Rio de Janeiro (-22.9068, -43.1729)
- **Data:** Temperatura, código climático, umidade, vento
- **License:** CC BY 4.0 (free for commercial use)

### **2. Caching Strategy**
- **Method:** WordPress Transients
- **TTL:** 30 minutos
- **Key:** `apollo_rio_weather`
- **Rationale:** Evita hit excessivo na API (OpenMeteo permite 10k requests/dia)

### **3. Weather Codes → RemixIcon Mapping**

| Condição | OpenMeteo Code | RemixIcon Class | pt-BR |
|----------|----------------|-----------------|-------|
| Céu limpo | 0 | `ri-sun-fill` | Céu limpo |
| Parcialmente nublado | 1-3 | `ri-cloud-fill` | Nublado |
| Neblina | 45-48 | `ri-mist-fill` | Neblina |
| Garoa | 51-55 | `ri-drizzle-fill` | Garoa |
| Chuva | 61-65 | `ri-rainy-fill` | Chuva |
| Pancadas | 80-82 | `ri-showers-fill` | Pancadas |
| Trovoada | 95-99 | `ri-thunderstorms-fill` | Trovoada |
| Granizo | 77 | `ri-hail-fill` | Granizo |

---

## 🔌 **Integração WordPress**

### **Filters (Auto-Connected)**

```php
// Filtros aplicados automaticamente
apply_filters('apollo_mural_weather_temp', '28°');      // Returns: "32°"
apply_filters('apollo_mural_weather_condition', 'Sunny'); // Returns: "Céu limpo"
apply_filters('apollo_mural_weather_icon', 'ri-sun-fill'); // Returns: "ri-sun-fill"
```

### **Template Usage**

Arquivo: `templates/template-parts/mural/weather-hero.php`

```php
$weather_temp      = apply_filters('apollo_mural_weather_temp', '28°');
$weather_condition = apply_filters('apollo_mural_weather_condition', 'Sunny');
$weather_icon      = apply_filters('apollo_mural_weather_icon', 'ri-sun-fill');
```

Os filtros são conectados automaticamente via `includes/weather-helpers.php`.

---

## 🛠️ **Testing & Debug**

### **1. Clear Cache**

```bash
# WP-CLI
wp transient delete apollo_rio_weather
```

### **2. Test API Fetch**

```bash
# WP-CLI command
wp apollo weather
```

**Output:**
```
Success: Weather fetched successfully:
Temperature: 32°
Condition: Céu limpo
Icon: ri-sun-fill
Code: 0
```

### **3. AJAX Refresh (Admin Only)**

```bash
# POST request
curl -X POST 'http://localhost:10004/wp-admin/admin-ajax.php?action=apollo_refresh_weather' \
  --cookie "wordpress_logged_in_xxx=..."
```

**Response:**
```json
{
  "success": true,
  "data": {
    "temp": "32°",
    "code": 0,
    "humidity": 65,
    "wind_speed": 12.5,
    "condition": "Céu limpo",
    "icon": "ri-sun-fill"
  }
}
```

---

## 🎨 **Frontend Display**

No **Mural** (`/mural`), o componente `weather-hero.php` exibe:

- **Live Cam:** YouTube embed (Copacabana)
- **Temperatura:** Tempo real (cached 30min)
- **Condição:** Texto em pt-BR
- **Ícone:** RemixIcon weather icon

---

## 📊 **API Limits**

| Feature | Limit | Notes |
|---------|-------|-------|
| Requests/day | 10,000 | OpenMeteo free tier |
| Cache TTL | 30 min | 48 requests/day = 0.48% usage |
| Timeout | 10s | wp_remote_get timeout |
| Error handling | Fallback to default | Never breaks page |

---

## 🔐 **Security**

- ✅ No API key required (public endpoint)
- ✅ Data sanitized via `esc_html()` / `esc_attr()`
- ✅ Error logging via `error_log()`
- ✅ AJAX endpoint requires `edit_posts` capability
- ✅ Transient caching prevents API abuse

---

## 🌍 **Why OpenMeteo?**

| Feature | OpenMeteo | OpenWeatherMap | WeatherAPI |
|---------|-----------|----------------|------------|
| **Price** | FREE | $0-40/mo | $0-100/mo |
| **No API Key** | ✅ | ❌ | ❌ |
| **Brazil Data** | ✅ | ✅ | ✅ |
| **License** | CC BY 4.0 | Proprietary | Proprietary |
| **Rate Limit** | 10k/day | 1k/day | 1M/mo |

OpenMeteo vence em:
- 🆓 Completamente gratuito
- 🔓 Sem autenticação
- 🌐 Open data (European Centre for Medium-Range Weather Forecasts)

---

## 📝 **Changelog**

**2026-02-09**
- ✅ Implementação inicial com OpenMeteo
- ✅ 30+ weather codes mapeados para RemixIcon
- ✅ Transient caching (30min TTL)
- ✅ WP-CLI command: `wp apollo weather`
- ✅ AJAX refresh endpoint
- ✅ Fallback para valores padrão em caso de erro
- ✅ 100% pt-BR strings

---

## 🤝 **Credits**

- **Weather Data:** [OpenMeteo](https://open-meteo.com/) - Open weather data by ECMWF
- **Icons:** [RemixIcon](https://remixicon.com/) - Apache 2.0
- **API Docs:** https://open-meteo.com/en/docs

---

## 📌 **Next Steps (Optional)**

1. **Historical Data:** Add 7-day forecast widget
2. **Multiple Locations:** Support Zona Sul, Centro, Barra
3. **Alerts:** Integrate severe weather warnings (INMET)
4. **Hourly:** Show hourly forecast for event planning
5. **UV Index:** Add UV data for outdoor events
