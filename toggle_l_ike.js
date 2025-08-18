// server.js
import express from "express";
import session from "express-session";
import mysql from "mysql2/promise";
import bodyParser from "body-parser";

const app = express();

// Middleware
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(session({
  secret: "your_secret_key",
  resave: false,
  saveUninitialized: true,
}));

// MySQL connection
const db = await mysql.createConnection({
  host: "localhost",
  user: "u807410800_capstoneappeco",
  password: "#@Tinauto500",
  database: "u807410800_capstoneapp"
});

// Toggle like endpoint
app.post("/toggle-like", async (req, res) => {
  try {
    // Check login
    if (!req.session.user_id) {
      return res.json({ error: "Not logged in" });
    }

    const { listing_id } = req.body;
    if (!listing_id) {
      return res.json({ error: "No listing id provided" });
    }

    // Fetch current liked value
    const [rows] = await db.execute(
      "SELECT liked FROM listings WHERE id = ?",
      [listing_id]
    );

    if (rows.length === 0) {
      return res.json({ error: "Listing not found" });
    }

    const currentLiked = parseInt(rows[0].liked, 10);
    const newLiked = currentLiked ? 0 : 1;

    // Update liked value
    await db.execute(
      "UPDATE listings SET liked = ? WHERE id = ?",
      [newLiked, listing_id]
    );

    return res.json({ status: "ok", liked: newLiked });

  } catch (err) {
    console.error(err);
    return res.json({ error: err.message });
  }
});

// Start server
app.listen(3000, () => {
  console.log("Server running on http://localhost:3000");
});
