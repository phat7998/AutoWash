"""
05_feature_importance.py — Top 5 yếu tố ảnh hưởng đến tier progression
TV4 - Tuần 9

Cách chạy:
    python scripts/ml/05_feature_importance.py

Output:
    data/results/05_top5_features.png
    data/results/05_feature_importance.csv
    data/results/results_summary.md
"""

import pandas as pd
import numpy as np
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from sklearn.ensemble import RandomForestClassifier, GradientBoostingClassifier
from sklearn.linear_model import LogisticRegression
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from sklearn.inspection import permutation_importance
import os

# ── Cấu hình ──────────────────────────────────────────────────────────────────
BASE_DIR    = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
DATA_DIR    = os.path.join(BASE_DIR, 'data')
RESULTS_DIR = os.path.join(DATA_DIR, 'results')
os.makedirs(RESULTS_DIR, exist_ok=True)

FEATURES = ['wash_count', 'lifetime_spend', 'avg_service_amount',
            'cancel_rate_pct', 'promo_usage_rate_pct']
TARGET   = 'tier_upgraded'

FEAT_LABELS = {
    'wash_count':           'Số lần rửa xe',
    'lifetime_spend':       'Tổng chi tiêu tích lũy',
    'avg_service_amount':   'Chi tiêu trung bình/lần',
    'cancel_rate_pct':      'Tỉ lệ hủy đặt lịch',
    'promo_usage_rate_pct': 'Tỉ lệ sử dụng khuyến mãi',
}

print("=" * 55)
print("  AutoWash — Feature Importance Analysis")
print("=" * 55)

# ── Load data ─────────────────────────────────────────────────────────────────
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
scaler     = StandardScaler()
X_train_sc = scaler.fit_transform(X_train)
X_test_sc  = scaler.transform(X_test)

print(f"\nLoaded: {len(df_clean)} customers\n")

# ── Tính feature importance từ 3 model ───────────────────────────────────────
print("[1/3] Tính importance từ Random Forest...")
rf = RandomForestClassifier(n_estimators=200, random_state=42, n_jobs=-1)
rf.fit(X_train, y_train)
imp_rf = rf.feature_importances_

print("[2/3] Tính importance từ Gradient Boosting...")
gb = GradientBoostingClassifier(n_estimators=100, random_state=42)
gb.fit(X_train, y_train)
imp_gb = gb.feature_importances_

print("[3/3] Tính importance từ Logistic Regression (permutation)...")
lr = LogisticRegression(random_state=42, max_iter=1000)
lr.fit(X_train_sc, y_train)
perm = permutation_importance(lr, X_test_sc, y_test, n_repeats=10, random_state=42)
imp_lr = np.abs(perm.importances_mean)
imp_lr = imp_lr / imp_lr.sum() if imp_lr.sum() > 0 else imp_lr  # normalize

# Tổng hợp: trung bình 3 model (normalize từng model trước)
def normalize(arr):
    return arr / arr.sum() if arr.sum() > 0 else arr

imp_combined = (normalize(imp_rf) + normalize(imp_gb) + normalize(imp_lr)) / 3

# Sort theo importance
indices  = np.argsort(imp_combined)[::-1]
top_feats = [available[i] for i in indices]
top_imps  = imp_combined[indices]
top_rf    = normalize(imp_rf)[indices]
top_gb    = normalize(imp_gb)[indices]
top_lr    = normalize(imp_lr)[indices]

print("\n" + "─" * 55)
print("TOP 5 YẾU TỐ ẢNH HƯỞNG ĐẾN TIER PROGRESSION")
print("─" * 55)
for rank, (feat, imp) in enumerate(zip(top_feats, top_imps), 1):
    label = FEAT_LABELS.get(feat, feat)
    print(f"  #{rank} {label:<35}: {imp:.4f} ({imp*100:.1f}%)")

# ── Biểu đồ: Top 5 features ───────────────────────────────────────────────────
fig, axes = plt.subplots(1, 2, figsize=(14, 6))
fig.suptitle('Top 5 Yếu Tố Ảnh Hưởng Đến Tier Progression', fontweight='bold', fontsize=14)

colors_top = ['#1B5E20', '#2E7D32', '#388E3C', '#43A047', '#66BB6A'][:len(top_feats)]
labels_top = [FEAT_LABELS.get(f, f) for f in top_feats]

# Combined importance
bars = axes[0].barh(labels_top, top_imps, color=colors_top,
                    edgecolor='white', linewidth=1.2)
axes[0].set_title('Combined Feature Importance\n(RF + GB + LR average)')
axes[0].set_xlabel('Importance Score (normalized)')
for bar, val in zip(bars, top_imps):
    axes[0].text(bar.get_width() + 0.005, bar.get_y() + bar.get_height()/2,
                 f'{val:.3f}', va='center', fontweight='bold', fontsize=10)
axes[0].set_xlim(0, max(top_imps) * 1.25)
axes[0].invert_yaxis()

# Grouped: RF vs GB vs LR
x   = np.arange(len(top_feats))
w   = 0.25
axes[1].barh(x + w,   top_rf, w, label='Random Forest',   color='#2E7D32', alpha=0.85)
axes[1].barh(x,       top_gb, w, label='Gradient Boost',  color='#1976D2', alpha=0.85)
axes[1].barh(x - w,   top_lr, w, label='Logistic Reg',    color='#E65100', alpha=0.85)
axes[1].set_yticks(x)
axes[1].set_yticklabels(labels_top)
axes[1].set_title('Importance theo từng Model')
axes[1].set_xlabel('Normalized Importance')
axes[1].legend(loc='lower right')
axes[1].invert_yaxis()

plt.tight_layout()
plt.savefig(os.path.join(RESULTS_DIR, '05_top5_features.png'), bbox_inches='tight')
plt.close()
print("\n→ Đã lưu 05_top5_features.png")

# ── Lưu CSV ───────────────────────────────────────────────────────────────────
df_imp = pd.DataFrame({
    'rank':             range(1, len(top_feats)+1),
    'feature':          top_feats,
    'label':            [FEAT_LABELS.get(f, f) for f in top_feats],
    'importance_combined': np.round(top_imps, 4),
    'importance_rf':    np.round(top_rf, 4),
    'importance_gb':    np.round(top_gb, 4),
    'importance_lr':    np.round(top_lr, 4),
})
df_imp.to_csv(os.path.join(RESULTS_DIR, '05_feature_importance.csv'), index=False)
print("→ Đã lưu 05_feature_importance.csv")

# ── Viết results_summary.md ───────────────────────────────────────────────────
summary_path = os.path.join(DATA_DIR, 'results_summary.md')
with open(summary_path, 'w', encoding='utf-8') as f:
    f.write("# AutoWash — Kết Quả Phân Tích & Machine Learning\n\n")
    f.write("## 1. Exploratory Data Analysis\n\n")
    f.write("| Tier | Số khách | Chi tiêu TB | Số lần rửa TB |\n")
    f.write("|------|----------|-------------|---------------|\n")

    tier_stats = df.groupby(tier_col).agg(
        count=(TARGET, 'count'),
        avg_spend=('lifetime_spend', 'mean'),
        avg_wash=('wash_count', 'mean')
    ).reindex(['MEMBER','SILVER','GOLD','PLATINUM'])

    for tier, row in tier_stats.iterrows():
        f.write(f"| {tier} | {int(row['count'])} | "
                f"{row['avg_spend']:,.0f}đ | {row['avg_wash']:.1f} |\n")

    f.write("\n## 2. Hypothesis Testing\n\n")
    f.write("| Hypothesis | Test | Kết quả |\n")
    f.write("|------------|------|----------|\n")
    f.write("| Số lần rửa ảnh hưởng đến tier | Kruskal-Wallis | BÁC BỎ H0 (p≈0.000) |\n")
    f.write("| Chi tiêu tương quan với tier | Spearman r=0.878 | BÁC BỎ H0 (p≈0.000) |\n")

    f.write("\n## 3. Machine Learning\n\n")

    lr_csv = os.path.join(RESULTS_DIR, '03_lr_results.csv')
    rf_csv = os.path.join(RESULTS_DIR, '04_rf_results.csv')
    if os.path.exists(rf_csv):
        df_res = pd.read_csv(rf_csv)
        f.write("| Model | Accuracy | Precision | Recall | F1 | AUC |\n")
        f.write("|-------|----------|-----------|--------|----|-----|\n")
        for _, row in df_res.iterrows():
            f.write(f"| {row['model']} | {row['accuracy']:.3f} | "
                    f"{row['precision']:.3f} | {row['recall']:.3f} | "
                    f"{row['f1']:.3f} | {row['auc']:.3f} |\n")

    f.write("\n## 4. Top 5 Yếu Tố Ảnh Hưởng Đến Tier Progression\n\n")
    f.write("| Rank | Yếu tố | Importance |\n")
    f.write("|------|--------|------------|\n")
    for _, row in df_imp.iterrows():
        f.write(f"| #{int(row['rank'])} | {row['label']} | {row['importance_combined']:.4f} |\n")

    f.write("\n## 5. Kết Luận\n\n")
    f.write(f"**Research Question:** *What factors most influence customer loyalty tier progression?*\n\n")
    f.write(f"Dựa trên phân tích từ {len(df_clean)} khách hàng và kết hợp 3 models (RF, GB, LR):\n\n")
    for rank, (feat, imp) in enumerate(zip(top_feats[:5], top_imps[:5]), 1):
        label = FEAT_LABELS.get(feat, feat)
        f.write(f"{rank}. **{label}** (importance={imp:.3f})\n")
    f.write(f"\nYếu tố quan trọng nhất: **{FEAT_LABELS.get(top_feats[0], top_feats[0])}**\n")

print("→ Đã lưu data/results_summary.md")

print("\n" + "=" * 55)
print("  KẾT LUẬN RESEARCH QUESTION")
print("=" * 55)
print("What factors most influence customer loyalty tier progression?")
print()
for rank, (feat, imp) in enumerate(zip(top_feats, top_imps), 1):
    label = FEAT_LABELS.get(feat, feat)
    bar   = '█' * int(imp * 50)
    print(f"  #{rank} {label:<35} {bar} {imp:.3f}")

print(f"\n→ Yếu tố quan trọng nhất: {FEAT_LABELS.get(top_feats[0], top_feats[0])}")
print("\nHoàn tất Feature Importance Analysis!")