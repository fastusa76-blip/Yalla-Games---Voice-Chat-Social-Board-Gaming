# Yalla Games

A responsive gaming storefront with WhatsApp-first ordering, private order tracking, a PHP/MySQL order API, an authenticated admin dashboard, and a server-side FAQ chatbot.

## Main features
- Responsive landing page and game-category cards.
- Customer order form saved to MySQL with a unique reference.
- Customer status lookup protected by a private random access token.
- Order statuses: pending, confirmed, rejected and fulfilled.
- Admin dashboard protected by a server-side API key.
- PHP FAQ chatbot endpoint.
- WhatsApp contact: **+92 306 7115462**.
- Email contact: **jabham786@gmail.com**.

## Backend setup and deployment
See **[BACKEND.md](BACKEND.md)** for PHP/MySQL requirements, database installation, environment variables, API routes and security instructions.

**PHP hosting is required. GitHub Pages cannot run this backend.** Do not test order submission by opening `index.html` as a local file; deploy the project to PHP hosting with MySQL configured first.

## Test automation
A GitHub Actions workflow runs PHP syntax checks and MySQL-backed API integration tests for health, chat, validation, order creation, private status lookup, admin authentication and confirmation.

## Important
A submitted order is initially **pending**. It becomes confirmed only after an authenticated administrator changes its status. The chatbot is a scripted FAQ assistant, not an AI model or live human agent. The contact email is a mail link; automatic email delivery is not configured.
