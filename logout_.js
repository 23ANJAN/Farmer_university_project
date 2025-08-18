// Add to src/routes/auth.routes.js (or create a separate Router)
import { Router } from "express";
export const logoutRouter = Router();

logoutRouter.post("/logout", (req, res) => {
  // Client should delete its token; optionally implement a blacklist
  res.json({ ok: true });
});
