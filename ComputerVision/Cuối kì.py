import cv2
import mediapipe as mp
import pyautogui
import time
import numpy as np
import math
from ctypes import cast, POINTER
from comtypes import CLSCTX_ALL
from pycaw.pycaw import AudioUtilities, IAudioEndpointVolume

# MediaPipe
mp_hands = mp.solutions.hands
hands = mp_hands.Hands(min_detection_confidence=0.7, min_tracking_confidence=0.7)
mp_draw = mp.solutions.drawing_utils

# Màn hình
screen_width, screen_height = pyautogui.size()

# Âm lượng
devices = AudioUtilities.GetSpeakers()
interface = devices.Activate(IAudioEndpointVolume._iid_, CLSCTX_ALL, None)
volume = cast(interface, POINTER(IAudioEndpointVolume))
volRange = volume.GetVolumeRange # kiểm tra khoảng âm lượng từ -65.25 đến 0.0
minVol = volRange()[0]
maxVol = volRange()[1]
# Webcam
cap = cv2.VideoCapture(0)
pTime = 0

while cap.isOpened(): # Mở webcam
    # Đọc từng frame từ webcam
    ret, frame = cap.read()
    if not ret:
        break

    frame = cv2.flip(frame, 1)
    frame_height, frame_width, _ = frame.shape

    rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
    results = hands.process(rgb_frame)

    if results.multi_hand_landmarks:
        for hand_landmarks in results.multi_hand_landmarks:
            mp_draw.draw_landmarks(frame, hand_landmarks, mp_hands.HAND_CONNECTIONS) # Vẽ các điểm trên tay

            def get_pos(id): # Lấy tọa độ của các điểm trên tay
                # id = 4: ngón cái, id = 8: ngón trỏ, id = 12: ngón giữa
                return int(hand_landmarks.landmark[id].x * frame_width), int(hand_landmarks.landmark[id].y * frame_height)

            x_index, y_index = get_pos(8)
            x_thumb, y_thumb = get_pos(4)
            x_middle, y_middle = get_pos(12)

            _, y_9 = get_pos(9)
            _, y_10 = get_pos(10)

            # Kiểm tra ngón giữa có gập hay không
            middle_folded = y_middle > y_10 and y_middle > y_9

            if middle_folded:
                # Chế độ điều khiển chuột 
                screen_x = int((x_index / frame_width) * screen_width)
                screen_y = int((y_index / frame_height) * screen_height)
                pyautogui.moveTo(screen_x, screen_y)

                if abs(x_index - x_thumb) < 40 and abs(y_index - y_thumb) < 40:
                    pyautogui.click()

                cv2.putText(frame, "CHE DO: CHUOT", (10, 60), cv2.FONT_HERSHEY_SIMPLEX, 1, (255, 255, 255), 2)
            else:
                # Chế độ điều chỉnh âm lượng 
                # Vẽ đường line giữa ngón trỏ và ngón cái 
                cv2.circle(frame, (x_middle, y_middle), 10, (0, 255, 255), -1)
                cv2.circle(frame, (x_thumb, y_thumb), 10, (0, 255, 255), -1)
                cv2.line(frame, (x_middle, y_middle), (x_thumb, y_thumb), (0, 255, 255), 3)

                # tính toán khoảng cách giữa ngón cái và ngón trỏ và điều chỉnh âm lượng
                length = math.hypot(x_middle - x_thumb, y_middle - y_thumb)
                vol = np.interp(length, [25, 250], [minVol, maxVol])
                volume.SetMasterVolumeLevel(vol, None)

                # vẽ thanh âm lượng
                volBar = np.interp(length, [25, 250], [400, 150])
                vol_percent = np.interp(length, [25, 250], [0, 100])
                cv2.rectangle(frame, (50, 150), (100, 400), (0, 255, 0), 3)
                cv2.rectangle(frame, (50, int(volBar)), (100, 400), (0, 255, 0), -1)
                cv2.putText(frame, f"{int(vol_percent)} %", (40, 130), cv2.FONT_HERSHEY_PLAIN, 2, (255, 0, 0), 2)

                cv2.putText(frame, "CHE DO: AM LUONG", (10, 60), cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 0), 2)

    # FPS
    cTime = time.time()
    fps = 1 / (cTime - pTime)
    pTime = cTime
    cv2.putText(frame, f"FPS: {int(fps)}", (500, 70), cv2.FONT_HERSHEY_PLAIN, 2, (255, 0, 0), 2)

    # Hiển thị
    cv2.imshow("Hand Control", frame)
    if cv2.waitKey(1) & 0xFF == ord('q'):
        break

cap.release()
cv2.destroyAllWindows()