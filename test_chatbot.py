#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import requests
import json

def test_chatbot():
    print("🤖 Testing Chatbot Connection...")
    
    # Test 1: Health check
    try:
        print("\n1️⃣ Testing health endpoint...")
        response = requests.get('http://127.0.0.1:5001/sante', timeout=5)
        if response.status_code == 200:
            print("✅ Health check OK:", response.json())
        else:
            print(f"❌ Health check failed: {response.status_code}")
            return False
    except requests.exceptions.ConnectionError:
        print("❌ Cannot connect to chatbot server!")
        print("💡 Make sure to run: python chatbot_ml/api_chatbot.py")
        return False
    except Exception as e:
        print(f"❌ Health check error: {e}")
        return False
    
    # Test 2: Chatbot message
    try:
        print("\n2️⃣ Testing chatbot message...")
        data = {
            "message": "Bonjour",
            "contexte": {
                "taches_total": 10,
                "taches_terminees": 5,
                "projets_actifs": 3
            }
        }
        
        response = requests.post(
            'http://127.0.0.1:5001/chatbot',
            json=data,
            headers={'Content-Type': 'application/json'},
            timeout=10
        )
        
        if response.status_code == 200:
            result = response.json()
            print("✅ Chatbot response OK:")
            print(f"   Message: {result.get('reponse', 'No response')}")
            print(f"   Language: {result.get('langue', 'Unknown')}")
            print(f"   Confidence: {result.get('confiance', 0)}")
            return True
        else:
            print(f"❌ Chatbot failed: {response.status_code}")
            print(f"   Response: {response.text}")
            return False
            
    except Exception as e:
        print(f"❌ Chatbot test error: {e}")
        return False

def check_dependencies():
    print("📦 Checking Python dependencies...")
    
    required = ['flask', 'flask_cors', 'pickle', 'requests']
    missing = []
    
    for package in required:
        try:
            if package == 'pickle':
                import pickle
            elif package == 'flask_cors':
                from flask_cors import CORS
            elif package == 'flask':
                import flask
            elif package == 'requests':
                import requests
            print(f"✅ {package}")
        except ImportError:
            print(f"❌ {package} - MISSING")
            missing.append(package)
    
    if missing:
        print(f"\n💡 Install missing packages:")
        print(f"   pip install {' '.join(missing)}")
        return False
    
    return True

if __name__ == '__main__':
    print("=" * 50)
    print("🔧 CHATBOT DIAGNOSTIC TOOL")
    print("=" * 50)
    
    # Check dependencies first
    if not check_dependencies():
        print("\n❌ Fix dependencies first!")
        exit(1)
    
    # Test chatbot
    if test_chatbot():
        print("\n🎉 Chatbot is working perfectly!")
        print("💡 You can now use the chatbot in your RH system.")
    else:
        print("\n❌ Chatbot has issues!")
        print("\n🔧 Troubleshooting steps:")
        print("1. Make sure Python server is running:")
        print("   cd chatbot_ml")
        print("   python api_chatbot.py")
        print("2. Check if port 5000 is free:")
        print("   netstat -an | findstr :5000")
        print("3. Try restarting the chatbot server")