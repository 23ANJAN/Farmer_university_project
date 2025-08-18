// src/routes/profile.routes.js
import express from "express";
import pool from "../db.js";
import { requireAuth } from "../middleware/auth.js";

const router = express.Router();

// GET /profile → my profile + my listings
router.get("/", requireAuth, async (req, res) => {
  try {
    const userId = req.user.id;
    const [[me]] = await pool.query(
      "SELECT id, name, email, role, created_at FROM users WHERE id = ?",
      [userId]
    );
    const [listings] = await pool.query(
      "SELECT id, title, price, state, city, created_at FROM listings WHERE user_id = ? ORDER BY created_at DESC",
      [userId]
    );
    res.json({ user: me, listings });
  } catch (e) {
    console.error(e);
    res.status(500).json({ error: "Failed to load profile" });
  }
});

export default router;
