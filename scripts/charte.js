// Ouvre le site en local avec la charte graphique (interne, jamais publiée) : http://localhost:8080/charte/
const { spawn } = require("child_process");
const eleventy = require.resolve("@11ty/eleventy/cmd.js");
spawn(process.execPath, [eleventy, "--serve"], { stdio: "inherit", env: { ...process.env, CHARTE: "1" } });
