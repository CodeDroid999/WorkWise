import express from "express";
import dotenv from "dotenv";
import cors from "cors";


dotenv.config();

const app = express();
const port = process.env.PORT;

app.listen(port, () => {
    console.log(`[server]: Server is running at http://localhost:${port}`);
  });