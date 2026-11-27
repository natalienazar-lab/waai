/* WAAI quick AI maturity assessment (method: WAAI Quick Assessment v0.3).
   The level is rule-based; AI only writes a short comment (assessment/ai.php).
   Switching language keeps the answers and the current step: only the texts change. */
(function(){
"use strict";
/* Booking page for the 30-minute call (e.g. Microsoft Bookings). Empty = the site's contact form. */
const BOOKING_URL = "";

const T={
"uk": {
"h1h": "Який рівень <em>ШІ-зрілості</em> у вашій компанії?",
"lede": "Чесна самооцінка за моделлю WAAI і план перших кроків.",
"f1": "5–7 хвилин",
"f2": "9–15 питань",
"f3": "Персональний звіт",
"youHere": "ви тут",
"leadH": "Кому надіслати результат?",
"leadP": "Звіт прийде на вашу робочу пошту. Ми не передаємо ваші дані третім особам.",
"role": "Посада (необов'язково)",
"consent": "Погоджуюся, що WAAI збереже мої відповіді та контакти, щоб надіслати звіт і зв'язатися зі мною.",
"of": "з",
"back": "Назад",
"yours": "Ваші відповіді",
"edit": "Змінити відповіді",
"sendTo": "Надіслати звіт на",
"book": "Записатися на дзвінок",
"sent": "Звіт надіслано на вашу пошту. Якщо листа немає за кілька хвилин, перевірте папку «Спам».",
"eyebrow": "WAAI · Швидка оцінка зрілості ШІ",
"h1": "Наскільки зріло ваша організація застосовує ШІ?",
"mFramework": "Фреймворк",
"mPrograms": "Програми",
"mAssess": "Оцінка зрілості",
"mContact": "Контакти",
"mCases": "Кейси",
"mBlog": "Блог",
"sOverview": "Огляд",
"sManifesto": "Маніфест",
"sPrinciples": "Принципи",
"sMaturity": "Модель зрілості",
"sRoles": "Ролі",
"footTag": "Відкрита методологія для організацій, де люди й ШІ працюють разом.",
"footStart": "Почати",
"ctaConsult": "Записатися на консультацію",
"agent": "WAAI агент",
"qn": "Питання",
"start": "Почати оцінку →",
"hello": "<p>Вітаю! Я допоможу оцінити, наскільки зріло ваша організація застосовує ШІ. Оберіть варіант, який найточніше описує, як ви працюєте <b>сьогодні</b>. Якщо практика різниться між командами, орієнтуйтеся на типовий підхід, а не на найсильніший приклад.</p><p class=\"hint\" style=\"margin-top:6px\">Потік — це наскрізний ланцюжок роботи, яким цінність доходить до клієнта.</p>",
"toL4": "Схоже, ви вже на рівні L3. Ще три питання, щоб перевірити рівень L4.",
"toL5": "Рівень L4 підтверджено. Останні три питання про рівень L5.",
"thanks": "Дякую! Ось ваш результат.",
"approx": "Орієнтовний рівень зрілості",
"means": "Що це означає",
"holds": "Що стримує перехід на",
"next": "Що далі",
"top": "Ви на найвищому рівні моделі. Поглиблена діагностика допоможе підтвердити його й знайти місця для подальшого розвитку.",
"aiH": "Коментар WAAI агента",
"aiWait": "Готую персональний коментар до ваших відповідей…",
"aiFallback": "Коментар зараз недоступний. Обговорімо ваш результат на дзвінку.",
"disc": "Це самооцінка одного представника організації без перевірки доказів. Детальний профіль за сімома вимірами дає поглиблена діагностика.",
"ctaH": "Що далі",
"name": "Ім'я",
"mail": "Робоча пошта",
"comp": "Компанія",
"again": "Пройти ще раз",
"ctaNote": "Обговорімо ваш результат і перші кроки на 30-хвилинному дзвінку.",
"sendLimit": "Звіт на цю адресу вже надсилали кілька разів за останню годину. Спробуйте пізніше або напишіть нам на info@wisely-applied-ai.com.",
"sendErr": "Не вдалося надіслати лист. Спробуйте ще раз або напишіть нам на info@wisely-applied-ai.com.",
"home": "Головна",
"sending": "Надсилаю…"
},
"en": {
"h1h": "What is your company's <em>AI maturity</em> level?",
"lede": "An honest self-check on the WAAI model, with your first next steps.",
"f1": "5–7 minutes",
"f2": "9–15 questions",
"f3": "Personal report",
"youHere": "you are here",
"leadH": "Where should we send your result?",
"leadP": "The report goes to your work email. We never share your details with third parties.",
"role": "Job title (optional)",
"consent": "I agree that WAAI stores my answers and contact details to send the report and contact me.",
"of": "of",
"back": "Back",
"yours": "Your answers",
"edit": "Change answers",
"sendTo": "Send the report to",
"book": "Book a call",
"sent": "The report is on its way to your inbox. If it hasn't arrived in a few minutes, check your spam folder.",
"eyebrow": "WAAI · Quick AI maturity assessment",
"h1": "How mature is your organisation's use of AI?",
"mFramework": "Framework",
"mPrograms": "Programs",
"mAssess": "Maturity assessment",
"mContact": "Contact",
"mCases": "Case studies",
"mBlog": "Blog",
"sOverview": "Overview",
"sManifesto": "Manifesto",
"sPrinciples": "Principles",
"sMaturity": "Maturity model",
"sRoles": "Roles",
"footTag": "An open methodology for organisations where people and AI work together.",
"footStart": "Get started",
"ctaConsult": "Book a consultation",
"agent": "WAAI agent",
"qn": "Question",
"start": "Start the assessment →",
"hello": "<p>Hello! I'll help you assess how maturely your organisation applies AI. Pick the option that best describes how you work <b>today</b>. If practice differs between teams, go by the typical approach, not the strongest single example.</p><p class=\"hint\" style=\"margin-top:6px\">A flow is an end-to-end chain of work through which value reaches the customer.</p>",
"toL4": "Looks like you're already at L3. Three more questions to check L4.",
"toL5": "L4 confirmed. Three final questions about L5.",
"thanks": "Thank you! Here is your result.",
"approx": "Approximate maturity level",
"means": "What this means",
"holds": "What holds you back from",
"next": "What's next",
"top": "You're at the top level of the model. An in-depth diagnostic can confirm it and show where to keep improving.",
"aiH": "Comment from the WAAI agent",
"aiWait": "Preparing a personal comment on your answers…",
"aiFallback": "The comment isn't available right now. Let's go through your result on a call.",
"disc": "This is a self-assessment by one person without checking evidence. A detailed profile across the seven dimensions comes from the in-depth diagnostic.",
"ctaH": "Next steps",
"name": "Name",
"mail": "Work email",
"comp": "Company",
"again": "Start again",
"ctaNote": "Let's go through your result and first steps on a 30-minute call.",
"sendLimit": "The report has already been sent to this address several times in the last hour. Please try again later or write to info@wisely-applied-ai.com.",
"sendErr": "We couldn't send the email. Please try again or write to info@wisely-applied-ai.com.",
"home": "Home",
"sending": "Sending…"
}
};
const L = "ABCDE";
let D = null;                       // LEVELS, LEVEL_TEXT, Q, RULES from data.json
let lang = "en";
try { const s = new URLSearchParams(location.search).get("lang") || localStorage.getItem("waai-lang"); if (s === "uk" || s === "en") lang = s; } catch (e) {}
let ans = [], lead = {}, path = 9;
let view = { name: "lead" };        // lead | q (with i, note) | report
let comments = {}, aiState = {};    // per language
let leadSentFor = "", reportSent = false, aiCtl = null;
const app = document.getElementById("chat");
const t = k => T[lang][k];
const esc = s => String(s).replace(/[&<>"]/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]));
const home = () => lang === "uk" ? "/uk/" : "/";

function applyLang(){
  document.documentElement.lang = lang;
  document.querySelectorAll("[data-i]").forEach(e => { const v = t(e.dataset.i); if (v != null) e.textContent = v; });
  document.getElementById("h1").innerHTML = t("h1h");
  document.title = (lang === "uk" ? "Оцінка зрілості ШІ" : "AI maturity assessment") + " · WAAI";
  document.querySelectorAll(".js-home").forEach(a => a.href = home());
  document.querySelectorAll(".js-link").forEach(a => a.href = home() + a.dataset.h);
  document.getElementById("lang-uk").setAttribute("aria-pressed", lang === "uk");
  document.getElementById("lang-en").setAttribute("aria-pressed", lang === "en");
}
function ladder(cur, done){
  const el = document.getElementById("ladder"); el.classList.toggle("has", !!cur);
  el.innerHTML = [1,2,3,4,5].map(n => `<div class="rung ${n === cur ? "on" : n <= done ? "done" : ""}" data-you="${t("youHere")}" title="L${n} · ${D.LEVELS[lang][n]}"><b>L${n}</b><small>${D.LEVELS[lang][n]}</small></div>`).join("");
}
function fails(level){ return D.RULES[level].filter(([q, min]) => ans[q] === undefined || ans[q] < min); }
function levelNow(){ let lv = 1; for (const n of [2,3,4,5]) { if (fails(n).length) break; lv = n; } return lv; }
function toTop(){ app.scrollIntoView({ behavior: "smooth", block: "start" }); }
function render(scroll){
  if (view.name === "lead") renderLead();
  else if (view.name === "q") renderQ(view.i, view.note);
  else renderReport();
  if (scroll) toTop();
}

/* Step 1: contact details first, so every completed assessment is a lead */
function renderLead(){
  ladder(0, 0);
  app.innerHTML = `<div class="msg agent"><div class="who"><span class="dot"></span>${t("agent")}</div><div class="bubble">${t("hello")}</div></div>
  <section class="card"><h3>${t("leadH")}</h3><p class="hint">${t("leadP")}</p>
  <form id="lead" class="lead">
    <label>${t("name")}<input id="lname" required autocomplete="name" maxlength="120" value="${esc(lead.name || "")}"></label>
    <label>${t("mail")}<input id="lmail" type="email" required autocomplete="email" maxlength="200" value="${esc(lead.mail || "")}"></label>
    <label>${t("comp")}<input id="lcomp" required autocomplete="organization" maxlength="200" value="${esc(lead.comp || "")}"></label>
    <label>${t("role")}<input id="lrole" autocomplete="organization-title" maxlength="120" value="${esc(lead.role || "")}"></label>
    <input class="hp" id="lhp" tabindex="-1" autocomplete="off" aria-hidden="true">
    <label class="chk"><input type="checkbox" id="lok" required ${lead.ok ? "checked" : ""}><span>${t("consent")}</span></label>
    <div class="row"><button class="btn" type="submit">${t("start")}</button></div>
  </form></section>`;
  const f = document.getElementById("lead");
  f.oninput = f.onchange = readLead;
  f.onsubmit = e => { e.preventDefault(); readLead(); view = { name: "q", i: 0 }; render(true); };
}
function readLead(){
  const v = id => (document.getElementById(id) || {}).value;
  if (!document.getElementById("lead")) return;
  lead = { name: v("lname").trim(), mail: v("lmail").trim(), comp: v("lcomp").trim(), role: v("lrole").trim(), ok: document.getElementById("lok").checked, hp: v("lhp") };
}

/* Step 2: one question at a time, with Back and a strip of earlier answers you can jump to */
function renderQ(i, note){
  ladder(0, 0);
  const q = D.Q[lang][i], cur = ans[i];
  const strip = ans.slice(0, path).map((k, j) => k === undefined ? "" : `<button class="chip ${j === i ? "now" : ""}" data-j="${j}" title="${esc(D.Q[lang][j].t)}">${j + 1}·${L[k]}</button>`).join("");
  app.innerHTML = (note ? `<div class="msg agent"><div class="who"><span class="dot"></span>${t("agent")}</div><div class="bubble">${t(note)}</div></div>` : "") +
  `<div class="qhead"><span>${t("qn")} ${i + 1} ${t("of")} ${path}</span><span>${Math.round(i / path * 100)}%</span></div>
  <div class="progress" aria-hidden="true"><i style="width:${Math.round(i / path * 100)}%"></i></div>
  <section class="card"><h3 class="qt">${q.t}</h3><div class="opts">${q.o.map((o, k) => `<button class="opt ${cur === k ? "sel" : ""}" data-k="${k}"><b>${L[k]}</b><span>${o}</span></button>`).join("")}</div>
  <div class="row nav2"><button class="btn ghost" id="back">← ${t("back")}</button></div></section>
  ${strip ? `<div class="strip"><span class="hint">${t("yours")}</span>${strip}</div>` : ""}`;
  app.querySelectorAll(".opt").forEach(b => b.onclick = () => { ans[i] = +b.dataset.k; next(i + 1); });
  document.getElementById("back").onclick = () => { view = i > 0 ? { name: "q", i: i - 1 } : { name: "lead" }; render(true); };
  app.querySelectorAll(".chip").forEach(c => c.onclick = () => { view = { name: "q", i: +c.dataset.j }; render(true); });
}
function next(i){
  if (i === 9) {
    if (!fails(2).length && !fails(3).length) { path = 12; view = { name: "q", i: 9, note: "toL4" }; return render(true); }
    path = 9; ans = ans.slice(0, 9); return finish();
  }
  if (i === 12) {
    if (!fails(4).length) { path = 15; view = { name: "q", i: 12, note: "toL5" }; return render(true); }
    path = 12; ans = ans.slice(0, 12); return finish();
  }
  if (i === 15) return finish();
  view = { name: "q", i }; render(true);
}

/* Step 3: the report, then send it and book a call */
function finish(){
  const key = ans.join("");
  if (key !== finish.last) { comments = {}; aiState = {}; reportSent = false; if (aiCtl) aiCtl.abort(); }
  finish.last = key;
  view = { name: "report" }; render(true);
}
function renderReport(){
  const lv = levelNow(); ladder(lv, lv);
  const gi = lang === "uk" ? 2 : 3;
  const gaps = lv < 5 ? fails(lv + 1).slice(0, 3).map(r => r[gi]) : [];
  const book = BOOKING_URL || home() + "#contact";
  app.innerHTML = `<section class="report">
    <div class="rhead"><div class="badge">L${lv}</div><div><small>${t("approx")} · ${esc(lead.comp)}</small><h2>${D.LEVELS[lang][lv]}</h2></div></div>
    <div class="rbody">
      <div><h3>${t("means")}</h3><p>${D.LEVEL_TEXT[lang][lv]}</p></div>
      ${gaps.length ? `<div><h3>${t("holds")} L${lv + 1}</h3><ul class="gaps">${gaps.map(g => `<li>${g}</li>`).join("")}</ul></div>` : `<div><h3>${t("next")}</h3><p>${t("top")}</p></div>`}
      <div><h3>${t("aiH")}</h3><p class="ai wait" id="ai">${t("aiWait")}</p></div>
      <details class="answers"><summary>${t("yours")} (${ans.length})</summary><ol>${ans.map((k, j) => `<li><b>${D.Q[lang][j].t}</b><br>${L[k]}. ${D.Q[lang][j].o[k]}</li>`).join("")}</ol><button class="btn ghost" id="edit">${t("edit")}</button></details>
      <p class="note">${t("disc")}</p>
      <div class="cta">
        <h3>${t("ctaH")}</h3>
        <div class="row">
          <button class="btn" id="sendrep" ${reportSent ? "disabled" : ""}>${t("sendTo")} ${esc(lead.mail)}</button>
          <a class="btn alt" id="book" href="${esc(book)}" ${BOOKING_URL ? 'target="_blank" rel="noopener"' : ""}>${t("book")}</a>
        </div>
        <p class="ok" id="ctaMsg" ${reportSent ? "" : "hidden"}>${reportSent ? t("sent") : ""}</p>
        <p class="note">${t("ctaNote")}</p>
      </div>
      <div class="row"><button class="btn ghost" id="again">${t("again")}</button></div>
    </div></section>`;
  document.getElementById("again").onclick = () => { ans = []; path = 9; view = { name: "lead" }; render(true); };
  document.getElementById("edit").onclick = () => { view = { name: "q", i: 0 }; render(true); };
  document.getElementById("sendrep").onclick = sendReport;
  showComment();
}

/* AI comment: streamed from the server, cached per language */
function showComment(){
  const out = document.getElementById("ai"); if (!out) return;
  const st = aiState[lang];
  if (st === "done") { out.classList.remove("wait"); out.textContent = comments[lang]; return; }
  if (st === "fail") { out.classList.remove("wait"); out.textContent = t("aiFallback"); return; }
  if (st === "loading") { if (comments[lang]) { out.classList.remove("wait"); out.textContent = comments[lang]; } return; }
  loadComment(lang);
}
async function loadComment(l){
  aiState[l] = "loading"; comments[l] = "";
  const key = ans.join("");
  aiCtl = new AbortController();
  const paint = () => { if (lang === l && view.name === "report") showComment(); };
  try {
    const r = await fetch("ai.php", { method: "POST", headers: { "content-type": "application/json" }, body: JSON.stringify({ lang: l, answers: ans }), signal: aiCtl.signal });
    if (!r.ok || !r.body) throw new Error("ai " + r.status);
    const rd = r.body.getReader(), dec = new TextDecoder();
    for (;;) {
      const { value, done } = await rd.read(); if (done) break;
      if (ans.join("") !== key) return;
      comments[l] += dec.decode(value, { stream: true }); paint();
    }
    comments[l] = comments[l].trim();
    aiState[l] = comments[l] ? "done" : "fail";
  } catch (e) {
    if (e.name === "AbortError" && ans.join("") !== key) return;
    aiState[l] = comments[l] ? "done" : "fail";
  }
  paint();
  postLead(l);
}

/* Send the result to the WAAI team once per set of answers (after the comment, so it is included) */
function payload(action, l){
  return JSON.stringify({ action, lang: l, answers: ans, name: lead.name, email: lead.mail, company: lead.comp, role: lead.role, consent: !!lead.ok, website: lead.hp || "", comment: aiState[l] === "done" ? comments[l] : "" });
}
function postLead(l){
  const key = ans.join("");
  if (leadSentFor === key) return;
  leadSentFor = key;
  fetch("report.php", { method: "POST", headers: { "content-type": "application/json" }, body: payload("lead", l), keepalive: true }).catch(() => {});
}
window.addEventListener("pagehide", () => {
  if (view.name === "report" && leadSentFor !== ans.join("") && navigator.sendBeacon) {
    leadSentFor = ans.join("");
    navigator.sendBeacon("report.php", new Blob([payload("lead", lang)], { type: "application/json" }));
  }
});
async function sendReport(e){
  const btn = e.currentTarget, msg = document.getElementById("ctaMsg");
  btn.disabled = true; msg.hidden = false; msg.className = "ok"; msg.textContent = t("sending");
  try {
    const r = await fetch("report.php", { method: "POST", headers: { "content-type": "application/json" }, body: payload("send", lang) });
    if (r.status === 429) { btn.disabled = false; msg.className = "err"; msg.textContent = t("sendLimit"); return; }
    const j = await r.json();
    if (!j.ok) throw new Error("send");
    reportSent = true; msg.textContent = t("sent");
  } catch (err) {
    btn.disabled = false; msg.className = "err"; msg.textContent = t("sendErr");
  }
}

/* Language switch: keep answers and step, re-render the current view */
function setLang(l){
  if (l === lang) return;
  if (view.name === "lead") readLead();
  lang = l;
  try { localStorage.setItem("waai-lang", l); } catch (e) {}
  try { const u = new URL(location.href); u.searchParams.set("lang", l); history.replaceState(null, "", u); } catch (e) {}
  applyLang(); render(false);
}
document.getElementById("lang-uk").onclick = () => setLang("uk");
document.getElementById("lang-en").onclick = () => setLang("en");

applyLang();
fetch("data.json?v=20261127").then(r => r.json()).then(d => { D = d; render(false); })
  .catch(() => { app.innerHTML = `<p class="err">${t("sendErr")}</p>`; });
})();
