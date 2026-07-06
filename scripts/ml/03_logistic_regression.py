"""
03_logistic_regression.py — Logistic Regression
TV4 - Tuần 9

Target: tier_upgraded (khách có lên tier cao hơn MEMBER không)
Features: wash_count, lifetime_spend, avg_service_amount, cancel_rate_pct, promo_usage_rate_pct

Cách chạy:
    python scripts/ml/03_logistic_regression.py

Output:
    data/results/03_confusion_matrix.png
    data/results/03_roc_curve.png
    data/results/03_lr_results.csv
"""

import pandas as pd
import numpy as np
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from sklearn.linear_model import LogisticRegression
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import (confusion_matrix, classification_report,
                              roc_curve, auc, accuracy_score,
                              precision_score, recall_score, f1_score)
import os, sys

# ── Cấu hình ──────────────────────────────────────────────────────────────────
BASE_DIR    = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
DATA_DIR    = os.path.join(BASE_DIR, 'data')
RESULTS_DIR = os.path.join(DATA_DIR, 'results')
os.makedirs(RESULTS_DIR, exist_ok=True)

FEATURES = ['wash_count', 'lifetime_spend', 'avg_service_amount',
            'cancel_rate_pct', 'promo_usage_rate_pct']
TARGET   = 'tier_upgraded'  # 1 nếu tier > MEMBER, 0 nếu MEMBER

print("=" * 55)
print("  AutoWash — Logistic Regression")
print("=" * 55)

# ── Load data ─────────────────────────────────────────────────────────────────
df = pd.read_csv(os.path.join(DATA_DIR, 'customers.csv'))
tier_col = 'tier' if 'tier' in df.columns else 'tier_code'
df[tier_col] = df[tier_col].astype(str).str.upper().str.strip()
df = df[df[tier_col].isin(['MEMBER','SILVER','GOLD','PLATINUM'])].copy()

# Tạo target variable
df[TARGET] = (df[tier_col] != 'MEMBER').astype(int)
print(f"\nLoaded: {len(df)} customers")
print(f"  Upgraded (tier > MEMBER): {df[TARGET].sum()} ({df[TARGET].mean()*100:.1f}%)")
print(f"  Member (không lên tier):  {(df[TARGET]==0).sum()} ({(df[TARGET]==0).mean()*100:.1f}%)")

# ── Chuẩn bị features ─────────────────────────────────────────────────────────
available = [f for f in FEATURES if f in df.columns]
print(f"\nFeatures dùng: {available}")

df_clean = df[available + [TARGET]].dropna()
X = df_clean[available].values
y = df_clean[TARGET].values

print(f"Samples sau khi bỏ NaN: {len(df_clean)}")

# ── Train/Test split ───────────────────────────────────────────────────────────
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, random_state=42, stratify=y
)
print(f"\nTrain: {len(X_train)}, Test: {len(X_test)}")

# Scale features
scaler  = StandardScaler()
X_train = scaler.fit_transform(X_train)
X_test  = scaler.transform(X_test)

# ── Train model ────────────────────────────────────────────────────────────────
model = LogisticRegression(random_state=42, max_iter=1000)
model.fit(X_train, y_train)

# ── Đánh giá ──────────────────────────────────────────────────────────────────
y_pred      = model.predict(X_test)
y_pred_prob = model.predict_proba(X_test)[:, 1]

accuracy  = accuracy_score(y_test, y_pred)
precision = precision_score(y_test, y_pred, zero_division=0)
recall    = recall_score(y_test, y_pred, zero_division=0)
f1        = f1_score(y_test, y_pred, zero_division=0)

# Cross-validation
cv_scores = cross_val_score(
    LogisticRegression(random_state=42, max_iter=1000),
    scaler.fit_transform(X), y, cv=5, scoring='accuracy'
)

print("\n" + "─" * 55)
print("KẾT QUẢ LOGISTIC REGRESSION")
print("─" * 55)
print(f"  Accuracy  : {accuracy:.4f} ({accuracy*100:.1f}%)")
print(f"  Precision : {precision:.4f}")
print(f"  Recall    : {recall:.4f}")
print(f"  F1-score  : {f1:.4f}")
print(f"  CV Accuracy (5-fold): {cv_scores.mean():.4f} ± {cv_scores.std():.4f}")

print("\nClassification Report:")
print(classification_report(y_test, y_pred,
      target_names=['Member (0)', 'Upgraded (1)']))

# Coefficients
print("Coefficients (feature importance):")
for feat, coef in sorted(zip(available, model.coef_[0]),
                          key=lambda x: abs(x[1]), reverse=True):
    print(f"  {feat:30s}: {coef:+.4f}")

# ── Biểu đồ 1: Confusion Matrix ───────────────────────────────────────────────
cm = confusion_matrix(y_test, y_pred)
fig, axes = plt.subplots(1, 2, figsize=(13, 5))
fig.suptitle('Logistic Regression — Kết Quả Phân Loại', fontweight='bold')

im = axes[0].imshow(cm, interpolation='nearest', cmap='Blues')
plt.colorbar(im, ax=axes[0])
axes[0].set_title('Confusion Matrix')
axes[0].set_xlabel('Predicted')
axes[0].set_ylabel('Actual')
axes[0].set_xticks([0, 1])
axes[0].set_yticks([0, 1])
axes[0].set_xticklabels(['Member', 'Upgraded'])
axes[0].set_yticklabels(['Member', 'Upgraded'])
for i in range(2):
    for j in range(2):
        axes[0].text(j, i, str(cm[i, j]), ha='center', va='center',
                     fontsize=16, fontweight='bold',
                     color='white' if cm[i, j] > cm.max()/2 else 'black')

# Metrics bar
metrics = {'Accuracy': accuracy, 'Precision': precision, 'Recall': recall, 'F1': f1}
bars = axes[1].bar(metrics.keys(), metrics.values(),
                   color=['#1976D2','#388E3C','#F57C00','#7B1FA2'],
                   edgecolor='white', linewidth=1.5)
axes[1].set_title('Metrics')
axes[1].set_ylim(0, 1.15)
axes[1].set_ylabel('Score')
for bar, val in zip(bars, metrics.values()):
    axes[1].text(bar.get_x() + bar.get_width()/2, bar.get_height() + 0.02,
                 f'{val:.3f}', ha='center', fontweight='bold')

plt.tight_layout()
plt.savefig(os.path.join(RESULTS_DIR, '03_confusion_matrix.png'), bbox_inches='tight')
plt.close()
print("\n→ Đã lưu 03_confusion_matrix.png")

# ── Biểu đồ 2: ROC Curve ──────────────────────────────────────────────────────
fpr, tpr, _ = roc_curve(y_test, y_pred_prob)
roc_auc = auc(fpr, tpr)

fig, ax = plt.subplots(figsize=(7, 6))
ax.plot(fpr, tpr, color='#1976D2', lw=2, label=f'ROC curve (AUC = {roc_auc:.3f})')
ax.plot([0, 1], [0, 1], color='gray', lw=1, linestyle='--', label='Random classifier')
ax.fill_between(fpr, tpr, alpha=0.1, color='#1976D2')
ax.set_xlim([0, 1])
ax.set_ylim([0, 1.05])
ax.set_xlabel('False Positive Rate')
ax.set_ylabel('True Positive Rate')
ax.set_title('ROC Curve — Logistic Regression', fontweight='bold')
ax.legend(loc='lower right')
ax.grid(alpha=0.3)

plt.tight_layout()
plt.savefig(os.path.join(RESULTS_DIR, '03_roc_curve.png'), bbox_inches='tight')
plt.close()
print("→ Đã lưu 03_roc_curve.png")

# ── Lưu kết quả ───────────────────────────────────────────────────────────────
results = pd.DataFrame([{
    'model': 'Logistic Regression',
    'accuracy': round(accuracy, 4),
    'precision': round(precision, 4),
    'recall': round(recall, 4),
    'f1': round(f1, 4),
    'auc': round(roc_auc, 4),
    'cv_accuracy_mean': round(cv_scores.mean(), 4),
    'cv_accuracy_std': round(cv_scores.std(), 4),
}])
results.to_csv(os.path.join(RESULTS_DIR, '03_lr_results.csv'), index=False)
print("→ Đã lưu 03_lr_results.csv")

print(f"\nAUC = {roc_auc:.4f}")
print("\nHoàn tất Logistic Regression!")