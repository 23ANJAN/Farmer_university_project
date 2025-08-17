// server.js
const express = require("express");
const mysql = require("mysql2");
const bcrypt = require("bcrypt");
const jwt = require("jsonwebtoken");
const bodyParser = require("body-parser");

const app = express();
app.use(bodyParser.json());

// --- Database Connection ---
const db = mysql.createConnection({
  host: "localhost",
  user: "root",
  password: "your_password",
  database: "farm_market"
});

db.connect(err => {
  if (err) throw err;
  console.log("✅ MySQL Connected");
});

// --- User Registration (Farmer/Consumer) ---
app.post("/register", async (req, res) => {
  const { username, email, password, role } = req.body; // role = farmer or consumer
  const hashedPass = await bcrypt.hash(password, 10);

  const query = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
  db.query(query, [username, email, hashedPass, role], (err, result) => {
    if (err) return res.status(500).send(err);
    res.json({ message: "User registered successfully" });
  });
});

// --- User Login ---
app.post("/login", (req, res) => {
  const { email, password } = req.body;
  db.query("SELECT * FROM users WHERE email = ?", [email], async (err, results) => {
    if (err || results.length === 0) return res.status(401).json({ error: "User not found" });

    const user = results[0];
    const isValid = await bcrypt.compare(password, user.password);
    if (!isValid) return res.status(403).json({ error: "Invalid password" });

    const token = jwt.sign({ id: user.id, role: user.role }, "secretKey", { expiresIn: "1h" });
    res.json({ token, role: user.role });
  });
});

// --- Add Product (Farmer) ---
app.post("/add-product", (req, res) => {
  const { farmer_id, name, price, quantity } = req.body;
  const query = "INSERT INTO products (farmer_id, name, price, quantity) VALUES (?, ?, ?, ?)";
  db.query(query, [farmer_id, name, price, quantity], (err, result) => {
    if (err) return res.status(500).send(err);
    res.json({ message: "Product added successfully" });
  });
});

// --- List All Products ---
app.get("/products", (req, res) => {
  db.query("SELECT * FROM products", (err, results) => {
    if (err) return res.status(500).send(err);
    res.json(results);
  });
});

// --- Negotiate Price ---
app.post("/negotiate", (req, res) => {
  const { product_id, buyer_id, offer_price } = req.body;
  const query = "INSERT INTO negotiations (product_id, buyer_id, offer_price) VALUES (?, ?, ?)";
  db.query(query, [product_id, buyer_id, offer_price], (err, result) => {
    if (err) return res.status(500).send(err);
    res.json({ message: "Offer submitted" });
  });
});

// --- Transaction Execution ---
app.post("/transaction", (req, res) => {
  const { product_id, buyer_id, final_price } = req.body;
  const query = "INSERT INTO transactions (product_id, buyer_id, final_price, status) VALUES (?, ?, ?, 'completed')";
  db.query(query, [product_id, buyer_id, final_price], (err, result) => {
    if (err) return res.status(500).send(err);
    res.json({ message: "Transaction completed successfully" });
  });
});

app.listen(3000, () => console.log("🚀 Server running on port 3000"));
