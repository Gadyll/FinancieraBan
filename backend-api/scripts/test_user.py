import json
import urllib.request
import urllib.parse
from urllib.error import HTTPError

BASE_URL = "http://127.0.0.1:8000/api/v1"

def test_user():
    req = urllib.request.Request(f"{BASE_URL}/auth/web-login", method="POST")
    req.add_header("Content-Type", "application/json")
    data = json.dumps({"username": "administrador_financiero", "password": "123456789"}).encode()
    try:
        res = urllib.request.urlopen(req, data=data)
        admin_data = json.loads(res.read())
        admin_token = admin_data["access_token"]
    except HTTPError as e:
        print("Admin login failed:", e.read().decode())
        return

    req = urllib.request.Request(f"{BASE_URL}/users", method="POST")
    req.add_header("Content-Type", "application/json")
    req.add_header("Authorization", f"Bearer {admin_token}")
    user_data = json.dumps({
        "username": "cobrador_test",
        "email": "c@c.com",
        "password": "Password123!",
        "role": "USER",
        "is_active": True
    }).encode()
    try:
        res = urllib.request.urlopen(req, data=user_data)
        print("Cobrador created")
    except HTTPError as e:
        if e.code == 409:
            print("Cobrador already exists")
        else:
            print("Cobrador create failed:", e.read().decode())
            return

    req = urllib.request.Request(f"{BASE_URL}/auth/web-login", method="POST")
    req.add_header("Content-Type", "application/json")
    data = json.dumps({"username": "cobrador_test", "password": "Password123!"}).encode()
    try:
        res = urllib.request.urlopen(req, data=data)
        cobrador_data = json.loads(res.read())
        cobrador_token = cobrador_data["access_token"]
    except HTTPError as e:
        print("Cobrador login failed:", e.read().decode())
        return

    req = urllib.request.Request(f"{BASE_URL}/loans?skip=0&limit=1000")
    req.add_header("Authorization", f"Bearer {cobrador_token}")
    try:
        res = urllib.request.urlopen(req)
        print("Loans GET OK:", res.status)
    except HTTPError as e:
        print("Loans GET failed:", e.status, e.read().decode())

    req = urllib.request.Request(f"{BASE_URL}/clients?skip=0&limit=1000")
    req.add_header("Authorization", f"Bearer {cobrador_token}")
    try:
        res = urllib.request.urlopen(req)
        print("Clients GET OK:", res.status)
    except HTTPError as e:
        print("Clients GET failed:", e.status, e.read().decode())


test_user()
