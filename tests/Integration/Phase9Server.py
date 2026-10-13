"""Serve the real app concurrently on Windows against a disposable test database."""

import mimetypes
import os
from pathlib import Path
import re
import shutil
import subprocess
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import unquote, urlsplit

ROOT = Path(__file__).resolve().parents[2]
PUBLIC = ROOT / "public"
PHP = shutil.which("php-cgi")
if not PHP or os.environ.get("APP_ENV") != "testing" or not re.fullmatch(
    r"photo_phase[89]_test_[a-f0-9]{16}", os.environ.get("DB_DATABASE", "")
):
    raise RuntimeError("Requires php-cgi and an explicitly selected disposable database.")


class Handler(BaseHTTPRequestHandler):
    def do_GET(self):
        self.respond()

    def do_POST(self):
        self.respond()

    def do_PUT(self):
        self.respond()

    def do_DELETE(self):
        self.respond()

    def respond(self):
        url = urlsplit(self.path)
        path = (PUBLIC / unquote(url.path).lstrip("/")).resolve()
        if path.is_relative_to(PUBLIC) and path.is_file() and path.suffix != ".php":
            content = path.read_bytes()
            self.send_response(200)
            self.send_header("Content-Type", mimetypes.guess_type(path)[0] or "application/octet-stream")
            self.send_header("Content-Length", str(len(content)))
            self.end_headers()
            self.wfile.write(content)
            return
        length = int(self.headers.get("Content-Length", "0"))
        env = os.environ.copy()
        env.update({
            "REDIRECT_STATUS": "200", "GATEWAY_INTERFACE": "CGI/1.1",
            "SCRIPT_FILENAME": str(PUBLIC / "index.php"), "SCRIPT_NAME": "/index.php",
            "DOCUMENT_ROOT": str(PUBLIC), "REQUEST_METHOD": self.command,
            "REQUEST_URI": self.path, "QUERY_STRING": url.query,
            "SERVER_NAME": "127.0.0.1", "SERVER_PORT": "8771",
            "SERVER_PROTOCOL": "HTTP/1.1", "REMOTE_ADDR": self.client_address[0],
            "CONTENT_LENGTH": str(length), "CONTENT_TYPE": self.headers.get("Content-Type", ""),
        })
        for key, value in self.headers.items():
            if key.lower() not in {"content-length", "content-type"}:
                env["HTTP_" + key.upper().replace("-", "_")] = value
        result = subprocess.run([PHP], cwd=ROOT, env=env, input=self.rfile.read(length),
                                stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=90)
        headers, separator, body = result.stdout.partition(b"\r\n\r\n")
        if not separator:
            self.send_error(502, "PHP-CGI did not return a valid response")
            return
        lines = headers.decode("latin-1").split("\r\n")
        status = next((int(line.split(":", 1)[1].strip().split()[0]) for line in lines
                       if line.lower().startswith("status:")), 200)
        self.send_response(status)
        for line in lines:
            name, _, value = line.partition(":")
            if name.lower() not in {"status", "connection", "transfer-encoding", "content-length"}:
                self.send_header(name, value.strip())
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)


if __name__ == "__main__":
    print("Disposable Phase 9 app: http://127.0.0.1:8771", flush=True)
    ThreadingHTTPServer(("127.0.0.1", 8771), Handler).serve_forever()
