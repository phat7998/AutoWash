"""
04_random_forest.py — Random Forest + So sánh với Logistic Regression
TV4 - Tuần 9

Cách chạy:
    python scripts/ml/04_random_forest.py

Output:
    data/results/04_rf_confusion_matrix.png
    data/results/04_rf_feature_importance.png
    data/results/04_model_comparison.png
    data/results/04_rf_results.csv
"""

import pandas as pd
import numpy as np
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from sklearn.ensemble import RandomForestClassifier
from sklearn.linear_model import LogisticRegression
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import (confusion_matrix, classification_report,
                              roc_curve, auc, accuracy_score,
                              precision_score, recall_score, f1_score)
import os

# ── Cấu hình ──────────────────────────────────────────────────────────────────
BASE_DIR    = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
DATA_DIR    = os.path.join(BASE_DIR, 'data')
RESULTS_DIR = os.path.join(DATA_DIR, 'results')
os.makedirs(RESULTS_DIR, exist_ok=True)

FEATURES = ['wash_count', 'lifetime_spend', 'avg_service_amount',
            'cancel_rate_pct', 'promo_usage_rate_pct']
TARGET   = 'tier_upgraded'

print("=" * 55)
print("  AutoWash — Random Forest")
print("=" * 55)

# ── Load & chuẩn bị data ──────────────────────────────────────────────────────
df = pd.read_csv(os.path.join(DATA_DIR, 'customers.csv'))
tier_col = 'tier' if 'tier' in df.columns else 'tier_code'
df[tier_col] = df[tier_col].astype(str).str.upper().str.strip()
df = df[df[tier_col].isin(['MEMBER','SILVER','GOLD','PLATINUM'])].copy()
df[TARGET] = (df[tier_col] != 'MEMBER').astype(int)

available = [f for f in FEATURES if f in df.columns]
df_clean  = df[available + [TARGET]].dropna()
X = df_clean[available].values
y = df_clean[TARGET].values

X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, random_state=42, stratify=y
)

scaler  = StandardScaler()
X_train_sc = scaler.fit_transform(X_train)
X_test_sc  = scaler.transform(X_test)

print(f"\nLoaded: {len(df_clean)} customers | Train: {len(X_train)}, Test: {len(X_test)}")

# ── Train Random Forest ────────────────────────────────────────────────────────
rf = RandomForestClassifier(n_estimators=100, random_state=42, n_jobs=-1)
rf.fit(X_train, y_train)

y_pred_rf   = rf.predict(X_test)
y_prob_rf   = rf.predict_proba(X_test)[:, 1]

acc_rf  = accuracy_score(y_test, y_pred_rf)
prec_rf = precision_score(y_test, y_pred_rf, zero_division=0)
rec_rf  = recall_score(y_test, y_pred_rf, zero_division=0)
f1_rf   = f1_score(y_test, y_pred_rf, zero_division=0)
fpr_rf, tpr_rf, _ = roc_curve(y_test, y_prob_rf)
auc_rf  = auc(fpr_rf, tpr_rf)
cv_rf   = cross_val_score(RandomForestClassifier(n_estimators=100, random_state=42),
                           X, y, cv=5, scoring='accuracy')

print("\n" + "─" * 55)
print("KẾT QUẢ RANDOM FOREST")
print("─" * 55)
print(f"  Accuracy  : {acc_rf:.4f} ({acc_rf*100:.1f}%)")
print(f"  Precision : {prec_rf:.4f}")
print(f"  Recall    : {rec_rf:.4f}")
print(f"  F1-score  : {f1_rf:.4f}")
print(f"  AUC       : {auc_rf:.4f}")
print(f"  CV Accuracy (5-fold): {cv_rf.mean():.4f} ± {cv_rf.std():.4f}")

print("\nClassification Report:")
print(classification_report(y_test, y_pred_rf,
      target_names=['Member (0)', 'Upgraded (1)']))

# ── Train Logistic Regression để so sánh ─────────────────────────────────────
lr = LogisticRegression(random_state=42, max_iter=1000)
lr.fit(X_train_sc, y_train)
y_pred_lr  = lr.predict(X_test_sc)
y_prob_lr  = lr.predict_proba(X_test_sc)[:, 1]
acc_lr     = accuracy_score(y_test, y_pred_lr)
prec_lr    = precision_score(y_test, y_pred_lr, zero_division=0)
rec_lr     = recall_score(y_test, y_pred_lr, zero_division=0)
f1_lr      = f1_score(y_test, y_pred_lr, zero_division=0)
fpr_lr, tpr_lr, _ = roc_curve(y_test, y_prob_lr)
auc_lr     = auc(fpr_lr, tpr_lr)
cv_lr      = cross_val_score(LogisticRegression(random_state=42, max_iter=1000),
                              X_train_sc, y_train, cv=5, scoring='accuracy')

# ── Biểu đồ 1: Confusion Matrix RF ───────────────────────────────────────────
cm_rf = confusion_matrix(y_test, y_pred_rf)
fig, axes = plt.subplots(1, 2, figsize=(13, 5))
fig.suptitle('Random Forest — Kết Quả Phân Loại', fontweight='bold')

im = axes[0].imshow(cm_rf, interpolation='nearest', cmap='Greens')
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
        axes[0].text(j, i, str(cm_rf[i, j]), ha='center', va='center',
                     fontsize=16, fontweight='bold',
                     color='white' if cm_rf[i, j] > cm_rf.max()/2 else 'black')

metrics = {'Accuracy': acc_rf, 'Precision': prec_rf, 'Recall': rec_rf, 'F1': f1_rf}
bars = axes[1].bar(metrics.keys(), metrics.values(),
                   color=['#2E7D32','#388E3C','#43A047','#66BB6A'],
                   edgecolor='white', linewidth=1.5)
axes[1].set_title('Metrics')
axes[1].set_ylim(0, 1.15)
for bar, val in zip(bars, metrics.values()):
    axes[1].text(bar.get_x() + bar.get_width()/2, bar.get_height() + 0.02,
                 f'{val:.3f}', ha='center', fontweight='bold')

plt.tight_layout()
plt.savefig(os.path.join(RESULTS_DIR, '04_rf_confusion_matrix.png'), bbox_inches='tight')
plt.close()
print("\n→ Đã lưu 04_rf_confusion_matrix.png")

# ── Biểu đồ 2: Feature Importance ────────────────────────────────────────────
importances = rf.feature_importances_
indices     = np.argsort(importances)[::-1]
sorted_feats = [available[i] for i in indices]
sorted_imps  = importances[indices]

feat_labels = {
    'wash_count':           'Số lần rửa xe',
    'lifetime_spend':       'Tổng chi tiêu',
    'avg_service_amount':   'Chi tiêu TB/lần',
    'cancel_rate_pct':      'Tỉ lệ hủy (%)',
    'promo_usage_rate_pct': 'Tỉ lệ dùng promo (%)',
}

fig, ax = plt.subplots(figsize=(9, 5))
colors_imp = ['#1B5E20','#2E7D32','#388E3C','#43A047','#66BB6A'][:len(sorted_feats)]
bars2 = ax.barh([feat_labels.get(f, f) for f in sorted_feats], sorted_imps,
                color=colors_imp, edgecolor='white', linewidth=1.2)
ax.set_title('Feature Importance — Random Forest', fontweight='bold')
ax.set_xlabel('Importance Score')
for bar, val in zip(bars2, sorted_imps):
    ax.text(bar.get_width() + 0.005, bar.get_y() + bar.get_height()/2,
            f'{val:.3f}', va='center', fontweight='bold')
ax.set_xlim(0, max(sorted_imps) * 1.2)
ax.invert_yaxis()

plt.tight_layout()
plt.savefig(os.path.join(RESULTS_DIR, '04_rf_feature_importance.png'), bbox_inches='tight')
plt.close()
print("→ Đã lưu 04_rf_feature_importance.png")

# ── Biểu đồ 3: So sánh 2 model ───────────────────────────────────────────────
fig, axes = plt.subplots(1, 2, figsize=(13, 5))
fig.suptitle('So Sánh Logistic Regression vs Random Forest', fontweight='bold')

# Metrics comparison
metric_names = ['Accuracy', 'Precision', 'Recall', 'F1', 'AUC']
lr_vals = [acc_lr, prec_lr, rec_lr, f1_lr, auc_lr]
rf_vals = [acc_rf, prec_rf, rec_rf, f1_rf, auc_rf]

x = np.arange(len(metric_names))
w = 0.35
axes[0].bar(x - w/2, lr_vals, w, label='Logistic Regression',
            color='#1976D2', edgecolor='white', linewidth=1.2)
axes[0].bar(x + w/2, rf_vals, w, label='Random Forest',
            color='#2E7D32', edgecolor='white', linewidth=1.2)
axes[0].set_xticks(x)
axes[0].set_xticklabels(metric_names)
axes[0].set_ylim(0, 1.2)
axes[0].set_ylabel('Score')
axes[0].set_title('Metrics Comparison')
axes[0].legend()
for i, (lv, rv) in enumerate(zip(lr_vals, rf_vals)):
    axes[0].text(i - w/2, lv + 0.02, f'{lv:.2f}', ha='center', fontsize=8)
    axes[0].text(i + w/2, rv + 0.02, f'{rv:.2f}', ha='center', fontsize=8)

# ROC comparison
axes[1].plot(fpr_lr, tpr_lr, color='#1976D2', lw=2,
             label=f'Logistic Regression (AUC={auc_lr:.3f})')
axes[1].plot(fpr_rf, tpr_rf, color='#2E7D32', lw=2,
             label=f'Random Forest (AUC={auc_rf:.3f})')
axes[1].plot([0,1],[0,1], 'gray', lw=1, linestyle='--')
axes[1].set_xlabel('False Positive Rate')
axes[1].set_ylabel('True Positive Rate')
axes[1].set_title('ROC Curve Comparison')
axes[1].legend(loc='lower right')
axes[1].grid(alpha=0.3)

plt.tight_layout()
plt.savefig(os.path.join(RESULTS_DIR, '04_model_comparison.png'), bbox_inches='tight')
plt.close()
print("→ Đã lưu 04_model_comparison.png")

# ── Lưu kết quả ───────────────────────────────────────────────────────────────
results = pd.DataFrame([
    {'model': 'Logistic Regression',
     'accuracy': round(acc_lr,4), 'precision': round(prec_lr,4),
     'recall': round(rec_lr,4), 'f1': round(f1_lr,4), 'auc': round(auc_lr,4)},
    {'model': 'Random Forest',
     'accuracy': round(acc_rf,4), 'precision': round(prec_rf,4),
     'recall': round(rec_rf,4), 'f1': round(f1_rf,4), 'auc': round(auc_rf,4)},
])
results.to_csv(os.path.join(RESULTS_DIR, '04_rf_results.csv'), index=False)
print("→ Đã lưu 04_rf_results.csv")

print("\n" + "─" * 55)
print("SO SÁNH 2 MODEL")
print("─" * 55)
print(f"{'Metric':<12} {'Logistic Reg':>14} {'Random Forest':>14}")
print("-" * 42)
for m, lv, rv in zip(metric_names, lr_vals, rf_vals):
    winner = '← LR' if lv > rv else ('← RF' if rv > lv else '   =')
    print(f"{m:<12} {lv:>14.4f} {rv:>14.4f}  {winner}")

better = 'Random Forest' if acc_rf > acc_lr else 'Logistic Regression'
print(f"\n→ Model tốt hơn: {better}")
print("\nHoàn tất Random Forest!")