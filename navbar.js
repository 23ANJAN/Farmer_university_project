// public/components/navbar.js
export function renderNavbarAuth(user) {
  const el = document.getElementById("navAuth");
  if (!el) return;
  if (user) {
    el.innerHTML = `
      <li class="nav-item"><a class="nav-link" href="/profile.html">${user.name}</a></li>
      <li class="nav-item"><a class="nav-link" href="#" id="logoutBtn">Logout</a></li>`;
    document.getElementById("logoutBtn")?.addEventListener("click", async (e) => {
      e.preventDefault();
      localStorage.removeItem("token");
      await fetch("/auth/logout", { method: "POST" });
      location.href = "/login.html";
    });
  } else {
    el.innerHTML = `<li class="nav-item"><a class="nav-link" href="/login.html">Login</a></li>`;
  }
}
