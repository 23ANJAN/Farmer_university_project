// updateProfile.js
import express from "express";
import session from "express-session";
import mysql from "mysql2/promise";
import multer from "multer";
import path from "path";
import fs from "fs";

const app = express();

// Session middleware (like PHP session_start)
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

// Multer setup for file uploads
const uploadDir = "profile_pic_new";
if (!fs.existsSync(uploadDir)) {
  fs.mkdirSync(uploadDir, { recursive: true });
}

const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, uploadDir);
  },
  filename: (req, file, cb) => {
    const uniqueName = Date.now() + "_" + file.originalname;
    cb(null, uniqueName);
  }
});

const upload = multer({
  storage,
  fileFilter: (req, file, cb) => {
    const ext = path.extname(file.originalname).toLowerCase();
    const allowed = [".jpg", ".jpeg", ".png", ".gif"];
    if (allowed.includes(ext)) {
      cb(null, true);
    } else {
      cb(new Error("Invalid file type. Allowed: jpg, jpeg, png, gif."));
    }
  }
});

// Update profile route
app.post("/update-profile", upload.single("profile_pic"), async (req, res) => {
  try {
    // Ensure user is logged in
    if (!req.session.user_id) {
      return res.redirect("/login");
    }

    const user_id = req.session.user_id;
    const { username, email, phone, based_in } = req.body;

    let newProfilePicPath = "";
    if (req.file) {
      newProfilePicPath = path.join(uploadDir, req.file.filename);
    }

    // Prepare SQL query
    let sql, params;
    if (newProfilePicPath) {
      sql = "UPDATE users SET username = ?, email = ?, phone = ?, based_in = ?, profile_pic = ? WHERE id = ?";
      params = [username, email, phone, based_in, newProfilePicPath, user_id];
    } else {
      sql = "UPDATE users SET username = ?, email = ?, phone = ?, based_in = ? WHERE id = ?";
      params = [username, email, phone, based_in, user_id];
    }

    // Execute query
    const [result] = await db.execute(sql, params);

    if (result.affectedRows > 0) {
      return res.redirect("/profile");
    } else {
      return res.status(500).send("Error updating profile");
    }
  } catch (err) {
    console.error(err);
    res.status(500).send("Server error: " + err.message);
  }
});

// Start server
app.listen(3000, () => {
  console.log("Server running on http://localhost:3000");
});
