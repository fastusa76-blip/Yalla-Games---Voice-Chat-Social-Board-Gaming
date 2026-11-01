const WHATSAPP_NUMBER = "923067115462";
const ORDER_EMAIL = "jabham786@gmail.com";

const modal = document.getElementById("orderModal");
const orderForm = document.getElementById("orderForm");
const gameChoice = document.getElementById("gameChoice");
const chatPanel = document.getElementById("chatPanel");
const chatLauncher = document.getElementById("chatLauncher");
const chatMessages = document.getElementById("chatMessages");

document.getElementById("year").textContent = new Date().getFullYear();

function openOrder(product = "General order") {
  gameChoice.value = product;
  modal.hidden = false;
  document.body.style.overflow = "hidden";
  window.setTimeout(() => document.getElementById("customerName").focus(), 50);
}
function closeOrder() {
  modal.hidden = true;
  document.body.style.overflow = "";
}
document.querySelectorAll("[data-order]").forEach(button => {
  button.addEventListener("click", () => openOrder(button.dataset.order));
});
document.getElementById("closeModal").addEventListener("click", closeOrder);
modal.addEventListener("click", event => { if (event.target === modal) closeOrder(); });
document.addEventListener("keydown", event => { if (event.key === "Escape") { closeOrder(); closeChat(); } });

orderForm.addEventListener("submit", event => {
  event.preventDefault();
  if (!orderForm.reportValidity()) return;
  const data = new FormData(orderForm);
  const details = [
    "Hello Yalla Games! I'd like to place an order.",
    "",
    "Name: " + data.get("name"),
    "My WhatsApp: " + data.get("phone"),
    "Game/Product: " + data.get("game"),
    "Quantity: " + data.get("quantity"),
    data.get("email") ? "Email: " + data.get("email") : "",
    data.get("notes") ? "Details: " + data.get("notes") : ""
  ].filter(Boolean).join("\n");
  window.open("https://wa.me/" + WHATSAPP_NUMBER + "?text=" + encodeURIComponent(details), "_blank", "noopener,noreferrer");
  closeOrder();
});

function addMessage(text, sender = "assistant") {
  const message = document.createElement("div");
  message.className = "message " + sender;
  message.textContent = text;
  chatMessages.appendChild(message);
  chatMessages.scrollTop = chatMessages.scrollHeight;
}
function openChat() {
  chatPanel.hidden = false;
  chatLauncher.setAttribute("aria-expanded", "true");
}
function closeChat() {
  chatPanel.hidden = true;
  chatLauncher.setAttribute("aria-expanded", "false");
}
chatLauncher.addEventListener("click", () => chatPanel.hidden ? openChat() : closeChat());
document.getElementById("chatClose").addEventListener("click", closeChat);

function assistantReply(raw) {
  const text = raw.toLowerCase();
  if (/order|buy|purchase|place/.test(text)) {
    addMessage("Absolutely! Tap the “Place an order” button below and share the game, quantity and your contact details. I'll prepare a WhatsApp message for our team.");
  } else if (/contact|email|whatsapp|number|phone/.test(text)) {
    addMessage("You can reach Yalla Games on WhatsApp at +92 306 7115462 or email jabham786@gmail.com.");
  } else if (/price|cost|available|stock/.test(text)) {
    addMessage("Our team can confirm current prices and availability. Tell us which game you're interested in and send the details on WhatsApp.");
  } else if (/game|recommend|suggest|choose/.test(text)) {
    addMessage("We feature board games, cards and party games, plus strategy and puzzle games. Tell us who you're playing with and what kind of game you enjoy — our team can help you choose.");
  } else if (/hello|hi|hey|salam|assalam/.test(text)) {
    addMessage("Hey! 👋 Welcome to Yalla Games. Are you looking for a game recommendation or would you like to place an order?");
  } else {
    addMessage("Thanks for your message! I can help with game categories, order steps and contact details. For specific stock, pricing or custom questions, message our team on WhatsApp: +92 306 7115462.");
  }
}
document.getElementById("chatForm").addEventListener("submit", event => {
  event.preventDefault();
  const input = document.getElementById("chatInput");
  const value = input.value.trim();
  if (!value) return;
  addMessage(value, "user");
  input.value = "";
  window.setTimeout(() => assistantReply(value), 250);
});
document.querySelectorAll("[data-chat]").forEach(button => button.addEventListener("click", () => {
  const value = button.dataset.chat;
  addMessage(value, "user");
  assistantReply(value);
}));

document.getElementById("menuToggle").addEventListener("click", event => {
  const nav = document.getElementById("navLinks");
  const open = nav.classList.toggle("open");
  event.currentTarget.setAttribute("aria-expanded", String(open));
});
document.querySelectorAll(".nav-links a").forEach(link => link.addEventListener("click", () => {
  document.getElementById("navLinks").classList.remove("open");
  document.getElementById("menuToggle").setAttribute("aria-expanded", "false");
}));

// Keep the email contact easy to reuse in the interface without pretending a form was sent.
document.querySelectorAll('a[href^="mailto:"]').forEach(link => link.setAttribute("href", "mailto:" + ORDER_EMAIL));