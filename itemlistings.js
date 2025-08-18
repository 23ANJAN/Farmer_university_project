import express from "express";
import pool from "../db.js";
import { requireAuth } from "../middleware/auth.js";

const router = express.Router();

// ✅ Get listing details
router.get("/:id", async (req, res) => {
  try {
    const [rows] = await pool.query("SELECT * FROM listings WHERE id = ?", [req.params.id]);
    if (!rows.length) return res.status(404).json({ error: "Listing not found" });
    res.json(rows[0]);
  } catch {
    res.status(500).json({ error: "Failed to fetch listing" });
  }
});

// ✅ Like a listing
router.post("/:id/like", requireAuth, async (req, res) => {
  try {
    await pool.query("INSERT INTO likes (user_id, listing_id) VALUES (?, ?)", [
      req.user.id,
      req.params.id,
    ]);
    res.json({ message: "Listing liked" });
  } catch {
    res.status(500).json({ error: "Failed to like" });
  }
});

// ✅ Add a new listing (converted from add-listing.php)
router.post("/add", requireAuth, async (req, res) => {
  const { title, description, price, location, category } = req.body;

  if (!title || !description || !price || !location || !category) {
    return res.status(400).json({ error: "All fields are required" });
  }

  try {
    const [result] = await pool.query(
      `INSERT INTO listings (title, description, price, location, category, user_id, created_at)
       VALUES (?, ?, ?, ?, ?, ?, NOW())`,
      [title, description, price, location, category, req.user.id]
    );

    res.status(201).json({ message: "Listing added successfully", listingId: result.insertId });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: "Failed to add listing" });
  }
});

export default router;
