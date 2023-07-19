import express from "express";
import dotenv from "dotenv";
import cors from "cors";


dotenv.config();

const app = express();
const port = process.env.PORT;

app.use(
  cors({
    origin: [process.env.ORIGIN],
    methods: ["GET", "POST", "PUT", "PATCH", "DELETE"],
    credentials: true,
  })
);

app.use(express.json());


app.listen(port, () => {
  console.log(`[server]: Server is running at http://localhost:${port}`);
});