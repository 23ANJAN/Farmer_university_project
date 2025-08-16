// src/routes/users.routes.js
import express from "express";
import pool from "../db.js";
import { requireAuth } from "../middleware/auth.js";

const router = express.Router();

// GET /users/me  → return profile/basic info
router.get("/me", requireAuth, async (req, res) => {
  try {
    const [rows] = await pool.query(
      "SELECT id, name, email, role, created_at FROM users WHERE id = ?",
      [req.user.id]
    );
    if (!rows[0]) return res.status(404).json({ error: "User not found" });
    res.json(rows[0]);
  } catch (e) {
    console.error(e);
    res.status(500).json({ error: "Failed to load user info" });
  }
});

export default router;
