const express = require("express");
const mysql = require("mysql2");
const bcrypt = require("bcrypt");
const session = require("express-session");
const bodyParser = require("body-parser");

const app = express();
const PORT = 3000;

// Middleware
app.use(bodyParser.urlencoded({ extended: true }));
app.use(express.static("public")); // serve frontend files
app.use(session({
    secret: "supersecretkey",
    resave: false,
    saveUninitialized: true
}));

// Database connection
const db = mysql.createConnection({
    host: "localhost",
    user: "u807410800_capstoneappeco",
    password: "#@Tinauto500",
    database: "u807410800_capstoneapp"
});

db.connect(err => {
    if (err) throw err;
    console.log("✅ MySQL Connected...");
});

// Handle user registration
app.post("/register", async (req, res) => {
    const { username, email, password } = req.body;

    // Hash password
    const hashedPassword = await bcrypt.hash(password, 10);

    // Insert into database
    db.query(
        "INSERT INTO users (username, email, password) VALUES (?, ?, ?)",
        [username, email, hashedPassword],
        (err, result) => {
            if (err) return res.status(500).send("Error: " + err.message);

            // Store session
            req.session.user_id = result.insertId;
            res.redirect("/index.html"); // send user to dashboard
        }
    );
});

// Start server
app.listen(PORT, () => {
    console.log(`🚀 Server running at http://localhost:${PORT}`);
});
