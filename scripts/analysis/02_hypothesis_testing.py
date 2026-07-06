"""
02_hypothesis_testing.py — Kiểm định giả thuyết thống kê
TV4 - Tuần 8

Cách chạy:
    python scripts/analysis/02_hypothesis_testing.py

Output:
    data/results/02_hypothesis_results.csv
    data/results/02_correlation_heatmap.png
    data/results/02_spend_vs_tier.png
    data/results/02_washcount_vs_tier.png
"""

import pandas as pd
import numpy as np
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import matplotlib.ticker as mticker
from scipy import stats
import os, sys

# ── Cấu hình ──────────────────────────────────────────────────────────────────
BASE_DIR    = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
DATA_DIR    = os.path.join(BASE_DIR, 'data')
RESULTS_DIR = os.path.join(DATA_DIR, 'results')
os.makedirs(RESULTS_DIR, exist_ok=True)

plt.rcParams['figure.dpi']      = 120
plt.rcParams['font.size']       = 11
plt.rcParams['axes.titlesize']  = 13
plt.rcParams['axes.titleweight']= 'bold'

TIER_ORDER  = ['MEMBER', 'SILVER', 'GOLD', 'PLATINUM']
TIER_COLORS = {
    'MEMBER':   '#78909C',
    'SILVER':   '#90A4AE',
    'GOLD':     '#FFB300',
    'PLATINUM': '#8E24AA',
}
# Mã số tier để tính correlation
TIER_NUMERIC = {'MEMBER': 1, 'SILVER': 2, 'GOLD': 3, 'PLATINUM': 4}

print("=" * 55)
print("  AutoWash — Hypothesis Testing")
print("=" * 55)

# ── Load data ─────────────────────────────────────────────────────────────────
customers_path = os.path.join(DATA_DIR, 'customers.csv')
if not os.path.exists(customers_path):
    sys.exit(f"[LỖI] Không tìm thấy {customers_path}")

df = pd.read_csv(customers_path)
tier_col = 'tier' if 'tier' in df.columns else 'tier_code'
df[tier_col] = df[tier_col].astype(str).str.upper().str.strip()
df = df[df[tier_col].isin(TIER_ORDER)].copy()
df['tier_num'] = df[tier_col].map(TIER_NUMERIC)

print(f"\nLoaded: {len(df)} customers\n")

hypothesis_results = []

# ══════════════════════════════════════════════════════
# H1: Số lần rửa xe ảnh hưởng đến tier progression
# ══════════════════════════════════════════════════════
print("─" * 55)
print("HYPOTHESIS 1:")
print("  H0: Số lần rửa xe KHÔNG khác nhau giữa các tier")
print("  H1: Số lần rửa xe CÓ khác nhau đáng kể giữa các tier")
print("─" * 55)

if 'wash_count' in df.columns:
    groups = [df[df[tier_col] == t]['wash_count'].dropna().values for t in TIER_ORDER]
    groups = [g for g in groups if len(g) > 1]

    # Kruskal-Wallis test (không yêu cầu phân phối chuẩn)
    h_stat, p_val = stats.kruskal(*groups)
    reject = p_val < 0.05

    print(f"\nKruskal-Wallis Test:")
    print(f"  H-statistic : {h_stat:.4f}")
    print(f"  p-value     : {p_val:.6f}")
    print(f"  Kết luận    : {'BÁC BỎ H0' if reject else 'CHẤP NHẬN H0'} (α=0.05)")
    if reject:
        print("  → Số lần rửa xe có ảnh hưởng đáng kể đến tier progression ✓")
    else:
        print("  → Chưa đủ bằng chứng cho thấy sự khác biệt")

    # Spearman correlation
    corr, corr_p = stats.spearmanr(df['wash_count'].dropna(),
                                    df.loc[df['wash_count'].notna(), 'tier_num'])
    print(f"\nSpearman Correlation (wash_count vs tier):")
    print(f"  r = {corr:.4f}, p = {corr_p:.6f}")
    strength = 'mạnh' if abs(corr) > 0.6 else ('trung bình' if abs(corr) > 0.3 else 'yếu')
    print(f"  → Tương quan {strength} {'thuận' if corr > 0 else 'nghịch'}")

    print("\nThống kê wash_count theo tier:")
    for tier in TIER_ORDER:
        sub = df[df[tier_col] == tier]['wash_count'].dropna()
        if not sub.empty:
            print(f"  {tier:10s}: mean={sub.mean():.1f}, median={sub.median():.1f}, std={sub.std():.1f}")

    hypothesis_results.append({
        'hypothesis': 'H1: Wash count ảnh hưởng đến tier',
        'test': 'Kruskal-Wallis',
        'statistic': round(h_stat, 4),
        'p_value': round(p_val, 6),
        'reject_h0': reject,
        'conclusion': 'Wash count có ảnh hưởng đáng kể đến tier' if reject else 'Chưa đủ bằng chứng',
        'spearman_r': round(corr, 4),
    })

# ══════════════════════════════════════════════════════
# H2: Chi tiêu tích lũy tương quan với tier
# ══════════════════════════════════════════════════════
print("\n" + "─" * 55)
print("HYPOTHESIS 2:")
print("  H0: Chi tiêu tích lũy KHÔNG tương quan với tier")
print("  H1: Chi tiêu tích lũy CÓ tương quan với tier")
print("─" * 55)

if 'lifetime_spend' in df.columns:
    valid = df[['lifetime_spend', 'tier_num']].dropna()

    # Spearman correlation
    corr2, p_corr2 = stats.spearmanr(valid['lifetime_spend'], valid['tier_num'])
    reject2 = p_corr2 < 0.05

    print(f"\nSpearman Correlation (lifetime_spend vs tier):")
    print(f"  r = {corr2:.4f}, p = {p_corr2:.6f}")
    print(f"  Kết luận: {'BÁC BỎ H0' if reject2 else 'CHẤP NHẬN H0'} (α=0.05)")
    strength2 = 'mạnh' if abs(corr2) > 0.6 else ('trung bình' if abs(corr2) > 0.3 else 'yếu')
    print(f"  → Tương quan {strength2} {'thuận' if corr2 > 0 else 'nghịch'} ✓" if reject2 else "")

    # ANOVA một chiều
    groups2 = [df[df[tier_col] == t]['lifetime_spend'].dropna().values for t in TIER_ORDER]
    groups2 = [g for g in groups2 if len(g) > 1]
    f_stat, p_anova = stats.f_oneway(*groups2)

    print(f"\nOne-way ANOVA (lifetime_spend across tiers):")
    print(f"  F-statistic : {f_stat:.4f}")
    print(f"  p-value     : {p_anova:.6f}")
    print(f"  Kết luận    : {'BÁC BỎ H0' if p_anova < 0.05 else 'CHẤP NHẬN H0'} (α=0.05)")

    print("\nThống kê lifetime_spend theo tier:")
    for tier in TIER_ORDER:
        sub = df[df[tier_col] == tier]['lifetime_spend'].dropna()
        if not sub.empty:
            print(f"  {tier:10s}: mean={sub.mean():>10,.0f}đ, median={sub.median():>10,.0f}đ")

    hypothesis_results.append({
        'hypothesis': 'H2: Lifetime spend tương quan với tier',
        'test': 'Spearman + ANOVA',
        'statistic': round(corr2, 4),
        'p_value': round(p_corr2, 6),
        'reject_h0': reject2,
        'conclusion': f'Tương quan {strength2} thuận (r={corr2:.3f})' if reject2 else 'Chưa đủ bằng chứng',
        'spearman_r': round(corr2, 4),
    })

# ── Export kết quả ────────────────────────────────────────────────────────────
df_results = pd.DataFrame(hypothesis_results)
df_results.to_csv(os.path.join(RESULTS_DIR, '02_hypothesis_results.csv'), index=False)
print(f"\n→ Đã lưu 02_hypothesis_results.csv")

# ── Biểu đồ 1: Correlation heatmap ───────────────────────────────────────────
print("\n[Biểu đồ 1] Correlation heatmap...")

num_cols = [c for c in ['lifetime_spend', 'wash_count', 'cancel_rate_pct',
                         'promo_usage_rate_pct', 'avg_service_amount', 'tier_num']
            if c in df.columns]

if len(num_cols) >= 2:
    corr_matrix = df[num_cols].corr(method='spearman')

    fig, ax = plt.subplots(figsize=(9, 7))
    im = ax.imshow(corr_matrix.values, cmap='RdYlGn', vmin=-1, vmax=1)
    plt.colorbar(im, ax=ax, label='Spearman r')

    ax.set_xticks(range(len(num_cols)))
    ax.set_yticks(range(len(num_cols)))
    labels = [c.replace('_', '\n') for c in num_cols]
    ax.set_xticklabels(labels, fontsize=9)
    ax.set_yticklabels(labels, fontsize=9)

    for i in range(len(num_cols)):
        for j in range(len(num_cols)):
            ax.text(j, i, f'{corr_matrix.values[i, j]:.2f}',
                    ha='center', va='center', fontsize=9,
                    color='black' if abs(corr_matrix.values[i, j]) < 0.7 else 'white')

    ax.set_title('Ma Trận Tương Quan Spearman', pad=15)
    plt.tight_layout()
    plt.savefig(os.path.join(RESULTS_DIR, '02_correlation_heatmap.png'), bbox_inches='tight')
    plt.close()
    print("    Đã lưu 02_correlation_heatmap.png")

# ── Biểu đồ 2: Spend vs tier ─────────────────────────────────────────────────
print("[Biểu đồ 2] Spend vs tier...")

if 'lifetime_spend' in df.columns:
    fig, ax = plt.subplots(figsize=(9, 5))
    colors_list = [TIER_COLORS[t] for t in TIER_ORDER]

    data_spend = [df[df[tier_col] == t]['lifetime_spend'].dropna().values for t in TIER_ORDER]
    bp = ax.boxplot(data_spend,tick_labels=TIER_ORDER, patch_artist=True, notch=False)
    for patch, color in zip(bp['boxes'], colors_list):
        patch.set_facecolor(color)
        patch.set_alpha(0.8)

    ax.set_title('Phân Phối Chi Tiêu Theo Tier (Hypothesis 2)')
    ax.set_xlabel('Tier')
    ax.set_ylabel('Lifetime Spend (đ)')
    ax.yaxis.set_major_formatter(mticker.FuncFormatter(lambda x, _: f'{x/1000:.0f}k'))

    # Thêm annotation p-value
    ax.text(0.98, 0.95, f'Spearman r={corr2:.3f}\np={p_corr2:.4f}',
            transform=ax.transAxes, ha='right', va='top',
            bbox=dict(boxstyle='round', facecolor='lightyellow', alpha=0.8))

    plt.tight_layout()
    plt.savefig(os.path.join(RESULTS_DIR, '02_spend_vs_tier.png'), bbox_inches='tight')
    plt.close()
    print("    Đã lưu 02_spend_vs_tier.png")

# ── Biểu đồ 3: Wash count vs tier ────────────────────────────────────────────
print("[Biểu đồ 3] Wash count vs tier...")

if 'wash_count' in df.columns:
    fig, ax = plt.subplots(figsize=(9, 5))
    data_wash = [df[df[tier_col] == t]['wash_count'].dropna().values for t in TIER_ORDER]
    bp2 = ax.boxplot(data_wash, tick_labels=TIER_ORDER, patch_artist=True)
    for patch, color in zip(bp2['boxes'], colors_list):
        patch.set_facecolor(color)
        patch.set_alpha(0.8)

    ax.set_title('Phân Phối Số Lần Rửa Xe Theo Tier (Hypothesis 1)')
    ax.set_xlabel('Tier')
    ax.set_ylabel('Số lần rửa xe')
    ax.text(0.98, 0.95, f'Kruskal-Wallis p={p_val:.4f}\nSpearman r={corr:.3f}',
            transform=ax.transAxes, ha='right', va='top',
            bbox=dict(boxstyle='round', facecolor='lightyellow', alpha=0.8))

    plt.tight_layout()
    plt.savefig(os.path.join(RESULTS_DIR, '02_washcount_vs_tier.png'), bbox_inches='tight')
    plt.close()
    print("    Đã lưu 02_washcount_vs_tier.png")

# ── Tóm tắt kết luận ──────────────────────────────────────────────────────────
print("\n" + "=" * 55)
print("  KẾT LUẬN HYPOTHESIS TESTING")
print("=" * 55)
for row in hypothesis_results:
    status = "✓ BÁC BỎ H0" if row['reject_h0'] else "✗ CHẤP NHẬN H0"
    print(f"\n{row['hypothesis']}")
    print(f"  {status} — {row['conclusion']}")

print(f"\nFiles output → data/results/")
print("  02_hypothesis_results.csv")
print("  02_correlation_heatmap.png")
print("  02_spend_vs_tier.png")
print("  02_washcount_vs_tier.png")
print("\nHoàn tất Hypothesis Testing!")