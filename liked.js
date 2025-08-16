// src/routes/likes.routes.js
import express from "express";
import pool from "../db.js";
import { requireAuth } from "../middleware/auth.js";

const router = express.Router();

// POST /likes/toggle  { listing_id }
router.post("/toggle", requireAuth, async (req, res) => {
  const { listing_id } = req.body;
  if (!listing_id) return res.status(400).json({ error: "listing_id required" });
  try {
    // Try insert like (unique constraint will prevent duplicates)
    try {
      await pool.query(
        "INSERT INTO likes (user_id, listing_id) VALUES (?, ?)",
        [req.user.id, listing_id]
      );
      return res.json({ liked: true });
    } catch (e) {
      // If already exists, delete it = unlike
      if (e.code === "ER_DUP_ENTRY") {
        await pool.query(
          "DELETE FROM likes WHERE user_id = ? AND listing_id = ?",
          [req.user.id, listing_id]
        );
        return res.json({ liked: false });
      }
      throw e;
    }
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: "Failed to toggle like" });
  }
});

// GET /likes/count/:listingId → count likes for a listing
router.get("/count/:listingId", async (req, res) => {
  try {
    const [rows] = await pool.query(
      "SELECT COUNT(*) AS count FROM likes WHERE listing_id = ?",
      [req.params.listingId]
    );
    res.json(rows[0] || { count: 0 });
  } catch (e) {
    console.error(e);
    res.status(500).json({ error: "Failed to fetch like count" });
  }
});

export default router;
