# AutoWash — Kết Quả Phân Tích & Machine Learning

## 1. Exploratory Data Analysis

| Tier | Số khách | Chi tiêu TB | Số lần rửa TB |
|------|----------|-------------|---------------|
| MEMBER | 168 | 193,214đ | 4.1 |
| SILVER | 88 | 875,682đ | 15.8 |
| GOLD | 26 | 2,115,769đ | 31.5 |
| PLATINUM | 5 | 3,130,000đ | 43.8 |

## 2. Hypothesis Testing

| Hypothesis | Test | Kết quả |
|------------|------|----------|
| Số lần rửa ảnh hưởng đến tier | Kruskal-Wallis | BÁC BỎ H0 (p≈0.000) |
| Chi tiêu tương quan với tier | Spearman r=0.878 | BÁC BỎ H0 (p≈0.000) |

## 3. Machine Learning

| Model | Accuracy | Precision | Recall | F1 | AUC |
|-------|----------|-----------|--------|----|-----|
| Logistic Regression | 0.983 | 1.000 | 0.958 | 0.979 | 1.000 |
| Random Forest | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 |

## 4. Top 5 Yếu Tố Ảnh Hưởng Đến Tier Progression

| Rank | Yếu tố | Importance |
|------|--------|------------|
| #1 | Tổng chi tiêu tích lũy | 0.6070 |
| #2 | Số lần rửa xe | 0.2936 |
| #3 | Tỉ lệ hủy đặt lịch | 0.0529 |
| #4 | Chi tiêu trung bình/lần | 0.0465 |
| #5 | Tỉ lệ sử dụng khuyến mãi | 0.0000 |

## 5. Kết Luận

**Research Question:** *What factors most influence customer loyalty tier progression?*

Dựa trên phân tích từ 285 khách hàng và kết hợp 3 models (RF, GB, LR):

1. **Tổng chi tiêu tích lũy** (importance=0.607)
2. **Số lần rửa xe** (importance=0.294)
3. **Tỉ lệ hủy đặt lịch** (importance=0.053)
4. **Chi tiêu trung bình/lần** (importance=0.046)
5. **Tỉ lệ sử dụng khuyến mãi** (importance=0.000)

Yếu tố quan trọng nhất: **Tổng chi tiêu tích lũy**
