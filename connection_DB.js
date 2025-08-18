// Import MySQL package (install with: npm install mysql2)
const mysql = require('mysql2');

// Database connection details
const connection = mysql.createConnection({
  host: "localhost",  // or "193.203.184.93"
  user: "u807410800_capstoneappeco",
  password: "#@Tinauto500",
  database: "u807410800_capstoneapp"
});

// Connect to the database
connection.connect((err) => {
  if (err) {
    console.error("❌ Connection failed: " + err.message);
    return;
  }
  console.log("✅ Database connected successfully!");
});

// Always close connection when done
// connection.end();
