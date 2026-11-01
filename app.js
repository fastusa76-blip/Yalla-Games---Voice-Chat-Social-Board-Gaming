const WHATSAPP_NUMBER = "923067115462";
const ORDER_EMAIL = "jabham786@gmail.com";
const API_URL = "api/index.php";

const modal = document.getElementById("orderModal");
const orderForm = document.getElementById("orderForm");
const gameChoice = document.getElementById("gameChoice");
const orderResult = document.getElementById("orderResult");
const chatPanel = document.getElementById("chatPanel");
const chatLauncher = document.getElementById("chatLauncher");
const chatMessages = document.getElementById("chatMessages");

document.getElementById("year").textContent = new Date().getFullYear();

function openOrder(product = "General order") {
  gameChoice.value = product;
  orderResult.hidden = true;
  orderResult.replaceChildren();
  modal.hidden = false;
  document.body.style.overflow = "hidden";
  window.setTimeout(() => document.getElementById("customerName").focus(), 50);
}
function closeOrder() {
  modal.hidden = true;
  document.body.style.overflow = "";
}
document.querySelectorAll("[data-order]").forEach(button => button.addEventListener("click", () => openOrder(button.dataset.order)));
document.getElementById("closeModal").addEventListener("click", closeOrder);
modal.addEventListener("click", event => { if (event.target === modal) closeOrder(); });
document.addEventListener("keydown", event => { if (event.key === "Escape") { closeOrder(); closeChat(); } });

function showOrderResult(message, isError = false) {
  orderResult.hidden = false;
  orderResult.replaceChildren();
  orderResult.className = "order-result" + (isError ? " error" : "");
  const p = document.createElement("p");
  p.textContent = message;
  orderResult.append(p);
}
orderForm.addEventListener("submit", async event => {
  event.preventDefault();
  if (!orderForm.reportValidity()) return;
  const submit = orderForm.querySelector('button[type="submit"]');
  const data = Object.fromEntries(new FormData(orderForm).entries());
  data.quantity = Number(data.quantity);
  submit.disabled = true;
  submit.textContent = "Saving order…";
  try {
    const response = await fetch(API_URL + "?action=orders", {
      method: "POST",
      headers: {"Content-Type":"application/json"},
      body: JSON.stringify(data)
    });
    const result = await response.json();
    if (!response.ok || !result.ok) throw new Error(result.error || "Could not save your order.");
    const reference = result.order.reference;
    sessionStorage.setItem("yalla-order-" + reference, result.order.access_token);
    showOrderResult("Order request saved. Reference: " + reference + ". Current status: PENDING. This is not yet a confirmed order.");
    document.getElementById("statusReference").value = reference;
    document.getElementById("statusToken").value = result.order.access_token;
    const track = document.createElement("p");
    track.textContent = "Save this private access token securely. It is required to check this order from another browser or device: " + result.order.access_token;
    orderResult.append(track);
    const wa = document.createElement("a");
    wa.className = "button";
    wa.href = "https://wa.me/" + WHATSAPP_NUMBER + "?text=" + encodeURIComponent("Hello Yalla Games, I submitted order " + reference + ". Please confirm availability and price.");
    wa.target = "_blank";
    wa.rel = "noopener noreferrer";
    wa.textContent = "Message Yalla Games on WhatsApp ↗";
    orderResult.append(wa);
    orderForm.reset();
    document.getElementById("quantity").value = "1";
  } catch (error) {
    showOrderResult(error.message + " Please check that the PHP backend is deployed and try again.", true);
  } finally {
    submit.disabled = false;
    submit.innerHTML = 'Submit order request <span>↗</span>';
  }
});

const statusForm = document.getElementById("statusForm");
statusForm.addEventListener("submit", async event => {
  event.preventDefault();
  const reference = document.getElementById("statusReference").value.trim();
  const token = document.getElementById("statusToken").value.trim() || sessionStorage.getItem("yalla-order-" + reference) || "";
  const output = document.getElementById("statusResult");
  output.textContent = "Checking order…";
  try {
    const url = API_URL + "?action=order-status&reference=" + encodeURIComponent(reference) + "&token=" + encodeURIComponent(token);
    const response = await fetch(url);
    const result = await response.json();
    if (!response.ok || !result.ok) throw new Error(result.error || "Could not check this order.");
    output.textContent = "Order " + result.order.reference + " — " + result.order.status.toUpperCase() + (result.order.admin_note ? ". Note: " + result.order.admin_note : "");
  } catch (error) {
    output.textContent = error.message;
  }
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

async function sendChatMessage(value) {
  addMessage(value, "user");
  try {
    const response = await fetch(API_URL + "?action=chat", {
      method: "POST",
      headers: {"Content-Type":"application/json"},
      body: JSON.stringify({message:value})
    });
    const result = await response.json();
    if (!response.ok || !result.ok) throw new Error(result.error || "Chat service unavailable.");
    addMessage(result.reply);
  } catch (error) {
    addMessage("Sorry, chat is temporarily unavailable. Please contact us on WhatsApp: +92 306 7115462.");
  }
}
document.getElementById("chatForm").addEventListener("submit", event => {
  event.preventDefault();
  const input = document.getElementById("chatInput");
  const value = input.value.trim();
  if (!value) return;
  input.value = "";
  sendChatMessage(value);
});
document.querySelectorAll("[data-chat]").forEach(button => button.addEventListener("click", () => sendChatMessage(button.dataset.chat)));

document.getElementById("menuToggle").addEventListener("click", event => {
  const nav = document.getElementById("navLinks");
  const open = nav.classList.toggle("open");
  event.currentTarget.setAttribute("aria-expanded", String(open));
});
document.querySelectorAll(".nav-links a").forEach(link => link.addEventListener("click", () => {
  document.getElementById("navLinks").classList.remove("open");
  document.getElementById("menuToggle").setAttribute("aria-expanded", "false");
}));
document.querySelectorAll('a[href^="mailto:"]').forEach(link => link.setAttribute("href", "mailto:" + ORDER_EMAIL));
