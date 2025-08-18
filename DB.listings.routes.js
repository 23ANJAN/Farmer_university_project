// src/routes/listings.routes.js
import express from "express";
import pool from "../db.js";

const router = express.Router();

// GET /listings/:id → with images and seller info
router.get("/:id", async (req, res) => {
  try {
    const id = req.params.id;
    const [listingRows] = await pool.query(
      `SELECT l.*, u.name AS seller_name, u.email AS seller_email
       FROM listings l JOIN users u ON u.id = l.user_id WHERE l.id = ?`,
      [id]
    );
    const listing = listingRows[0];
    if (!listing) return res.status(404).json({ error: "Not found" });

    const [imageRows] = await pool.query(
      "SELECT id, image_path FROM listing_images WHERE listing_id = ? ORDER BY id ASC",
      [id]
    );

    // like count (+ optionally whether current user liked it)
    const [likeCountRows] = await pool.query(
      "SELECT COUNT(*) AS count FROM likes WHERE listing_id = ?",
      [id]
    );

    res.json({ listing, images: imageRows, likes: likeCountRows[0].count });
  } catch (e) {
    console.error(e);
    res.status(500).json({ error: "Failed to load listing" });
  }
});

export default router;
