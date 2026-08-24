# Modern Greeting System - Apollo Mural

Sistema de saudações dinâmico baseado em **hora + dia da semana + energia**.

---

## 🎯 **Objetivo**

Criar saudações personalizadas que:
- ✅ Refletem o momento do dia
- ✅ Capturam a energia do dia da semana
- ✅ Agitam nas quintas/sextas à noite (party mode)
- ✅ Acalmam em dias de semana (respiração/meditação)
- ✅ Variam aleatoriamente para não repetir

---

## 📅 **Lógica por Dia da Semana**

### **🔥 Quinta-feira à Noite (18h+)**
**Vibe:** Esquenta de fim de semana
**Mensagens:**
- "Esquenta de fim de semana! 🔥"
- "A noite é uma criança! ✨"
- "Quinta já é sexta no Rio! 🌆"
- "Bora pra rua! 🎉"

**Ícone:** `ri-fire-fill`

---

### **🚀 Sexta-feira à Noite (18h+)**
**Vibe:** PARTY MODE
**Mensagens:**
- "Sextou com força! 🎊"
- "A cidade te espera! 🌃"
- "Noite de balada! 💃"
- "Bora agitar! 🚀"
- "Energia total! ⚡"

**Ícone:** `ri-rocket-fill`

---

### **🌺 Sexta-feira de Dia (até 18h)**
**Vibe:** Pre-party energy
**Mensagens:**
- "Sexta-feira florida! 🌺"
- "O fim de semana começa agora! 🎯"
- "Energia boa no ar! ☀️"

**Ícone:** `ri-sparkling-fill`

---

### **🏖️ Sábado/Domingo**
**Vibe:** Weekend chill

**Manhã (até 12h):**
- "Fim de semana merece descanso! 🛋️"
- "Respira fundo e aproveita! 🌊"
- "Momento de recarregar! 🔋"

**Tarde/Noite:**
- "Curte cada momento! 🎵"
- "O Rio está on! 🏖️"
- "Vibe de fim de semana! 🌴"

**Ícone:** `ri-sun-cloudy-fill` (manhã) / `ri-music-2-fill` (tarde)

---

### **🌱 Segunda-feira**
**Vibe:** Restart com propósito
**Mensagens:**
- "Segunda com propósito! 💪"
- "Respira fundo. Nova semana! 🧘"
- "Um passo de cada vez! 🚶"
- "Recomeço é vida! 🌱"

**Ícone:** `ri-seedling-fill`

---

### **🧘 Terça/Quarta**
**Vibe:** CALM & FOCUS - Respiração/Meditação
**Mensagens:**
- "Respira e segue em frente! 🌬️"
- "Calma e foco! 🎯"
- "Medita no presente! 🧘‍♀️"
- "Energia equilibrada! ⚖️"
- "Pausa pra respirar! 💙"

**Ícone:** `ri-heart-pulse-fill`

---

### **🌙 Madrugada (23h-3h - Todos os dias)**
**Vibe:** Late night chill
**Mensagens:**
- "Momento de silêncio e música! 🎧"
- "A madrugada tem sua magia! ✨"
- "Respira fundo. Tá quase! 🌙"

**Ícone:** `ri-moon-fill`

---

## ⏰ **Saudações Base (por Hora)**

| Hora | Saudação |
|------|----------|
| 0h-5h | Boa madrugada |
| 6h-11h | Bom dia |
| 12h-17h | Boa tarde |
| 18h-23h | Boa noite |

---

## 🎨 **Estrutura HTML**

```html
<header class="mural-greeting">
    <h1 class="greet-title">Boa noite, Rafael!</h1>
    
    <!-- VIBE MESSAGE - Dinâmica -->
    <div class="greet-vibe">
        <i class="ri-fire-fill"></i>
        <span>Esquenta de fim de semana! 🔥</span>
    </div>

    <div class="greet-temp">
        <i class="ri-map-pin-2-fill"></i>
        Copacabana
    </div>

    <div class="greet-alert">
        Não perca <strong>Techno Night</strong> hoje no <strong>Club X</strong>.
    </div>
</header>
```

---

## 🎯 **CSS Animações**

### **`.greet-vibe`**
- Background gradiente (vermelho → amarelo)
- Border left: 3px primary
- Animation: `slideInFromLeft` (0.5s)
- Ícone com `pulseIcon` (2s loop)

### **Animações:**

```css
@keyframes slideInFromLeft {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes pulseIcon {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}
```

---

## 🧪 **Testing**

### **Método 1: Change WordPress Time**

```php
// In wp-config.php
define('WP_DEBUG', true);

// In greeting.php (temporary)
$hour = 19; // Test Thursday night
$weekday = 4; // Thursday
```

### **Método 2: Browser DevTools**

1. Inspecionar `.greet-vibe`
2. Editar HTML diretamente
3. Testar diferentes mensagens

### **Método 3: WP-CLI**

```bash
# Set current time to Friday 20h
wp eval "update_option('gmt_offset', -3); echo 'Timezone: ' . current_time('mysql');"
```

---

## 📊 **Probabilidades**

Cada array de mensagens usa `array_rand()`:
- **Quinta noite:** 4 variações (25% cada)
- **Sexta noite:** 5 variações (20% cada)
- **Terça/Quarta:** 5 variações (20% cada)
- **Segunda:** 4 variações (25% cada)
- **Fim de semana:** 3 variações (33% cada)
- **Madrugada:** 3 variações (33% cada)

---

## 🎭 **Philosophy**

| Dia | Energia | Objetivo |
|-----|---------|----------|
| **Seg** | Recomeço | Motivar sem pressionar |
| **Ter/Qua** | Calma | Respiração, meditação, foco |
| **Qui noite** | Esquenta | Preparar pra festa |
| **Sex noite** | PARTY | AGITAR completo |
| **Sáb/Dom** | Chill/On | Aproveitar sem culpa |
| **Madrugada** | Magia | Silêncio e introspecção |

---

## 🔄 **Randomização**

Cada acesso ao mural gera nova mensagem via `array_rand()`:
- Evita repetição excessiva
- Mantém frescor
- Usuário descobre novas vibes

---

## 🚀 **Future Enhancements**

1. **User Preferences:** Permitir escolher vibe (sempre agitar vs sempre calm)
2. **Weather Integration:** "Chuva no radar! Vibe indoor! 🌧️"
3. **Event-based:** "Tem rave hoje! Bora! 🎉"
4. **User Activity:** "Você não posta há 3 dias. Bora dar as caras! 👋"
5. **Seasonal:** Carnaval, réveillon, festas específicas

---

## ✅ **Verification Checklist**

- [x] Base greeting por hora funciona
- [x] Weekday detection (1-7)
- [x] Qui/Sex noite = party vibes
- [x] Terça/Qua = calm vibes
- [x] Segunda = restart vibes
- [x] Fim de semana = chill/on vibes
- [x] Madrugada override funciona
- [x] Randomização via array_rand
- [x] CSS animations funcionam
- [x] Ícones RemixIcon carregam
- [x] Responsive (mobile/desktop)

---

## 🎨 **Design System**

**Colors:**
- Primary: `var(--primary)` (#FF6B6B aprox)
- Background: Linear gradient rgba(255,107,107,0.1) → rgba(255,193,7,0.1)
- Text: `var(--black-1)`

**Typography:**
- Vibe: `var(--ff-main)` 14px weight 600
- Title: `var(--ff-main)` clamp(32px, 4vw, 56px) weight 700

**Icons:**
- Size: 18px
- Color: `var(--primary)`
- Animation: pulseIcon 2s infinite

---

**Status:** ✅ PRODUCTION READY  
**Version:** 1.0.0  
**Date:** 2026-02-09
