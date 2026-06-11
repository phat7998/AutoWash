import cv2
import matplotlib.pyplot as plt
import numpy as np

# Đọc hình ảnh từ tệp
img_cb = cv2.imread("Lesson 1/haha.jpg")

min_pixel = np.min(img_cb)
max_pixel = np.max(img_cb)

img_contrast = ((img_cb - min_pixel) / (max_pixel - min_pixel) * 255).astype(np.uint8)

plt.figure(figsize=(10, 5))
plt.subplot(1, 2, 1); plt.imshow(img_cb); plt.title("Original Image")
plt.subplot(1, 2, 2); plt.imshow(img_contrast); plt.title("Contrast Image")
plt.show()