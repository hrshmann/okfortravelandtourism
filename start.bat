@echo off
title OK FOR TRAVEL AND TOURISM CO
cd /d "%~dp0"
start "" http://localhost:8000
node server.js
pause
