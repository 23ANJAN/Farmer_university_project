// routes/messages.routes.js
import express from "express";
import pool from "../db.js";   // mysql2 pool
import { requireAuth } from "../middleware/auth.js";

const router = express.Router();

// GET unread messages count
router.get("/unread-count", requireAuth, async (req, res) => {
  try {
    const user_id = req.user.id; // from JWT

    const [rows] = await pool.query(
      "SELECT COUNT(*) as unread FROM messages WHERE receiver_id = ? AND is_read = 0",
      [user_id]
    );

    res.json(rows[0]);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: "Failed to fetch unread count" });
  }
});

export default router;
