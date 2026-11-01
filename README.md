# Yalla Games

A responsive, static storefront starter for Yalla Games with a WhatsApp-first order flow and a lightweight customer-help chat widget.

## Run locally

No build step or package installation is required. Open `index.html` in a browser, or serve the folder with any static web server.

## Included

- Responsive landing page and game-category cards.
- Order form that validates customer details and prepares a WhatsApp message.
- WhatsApp order destination: **+92 306 7115462**.
- Email contact: **jabham786@gmail.com**.
- Floating **Yalla Order Assistant** with scripted answers for common questions.
- No payment collection and no false order-confirmation state.

## Important limitations

This first version is front-end only. The chat assistant uses predefined responses; it is not an AI/live-agent system. The order form opens WhatsApp with a prefilled message and does not store orders on a server or send an email automatically. The customer must press Send in WhatsApp, and the Yalla Games team must confirm availability, price and the order.

For production order management, add a secure backend/database, email delivery provider, privacy notice, spam protection and a real-time support/AI service. Never put API keys or email-provider credentials in browser JavaScript.

## Contact configuration

Update `WHATSAPP_NUMBER` and `ORDER_EMAIL` at the top of `app.js` if the business contact details change.