"""
01_exploratory_analysis.py — Phân tích thống kê mô tả
TV4 - Tuần 8

Cách chạy:
    python scripts/analysis/01_exploratory_analysis.py

Output:
    data/results/01_summary_stats.csv
    data/results/01_tier_distribution.png
    data/results/01_spending_by_tier.png
    data/results/01_booking_frequency.png
    data/results/01_cancel_rate_by_tier.png
"""

import pandas as pd
import numpy as np
import matplotlib
matplotlib.use('Agg')  # không cần GUI
import matplotlib.pyplot as plt
import matplotlib.ticker as mticker
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

print("=" * 55)
print("  AutoWash — Exploratory Data Analysis")
print("=" * 55)

# ── Load data ─────────────────────────────────────────────────────────────────
customers_path = os.path.join(DATA_DIR, 'customers.csv')
bookings_path  = os.path.join(DATA_DIR, 'bookings.csv')

if not os.path.exists(customers_path):
    sys.exit(f"[LỖI] Không tìm thấy {customers_path}\n       Chạy export_data.php trước.")
if not os.path.exists(bookings_path):
    sys.exit(f"[LỖI] Không tìm thấy {bookings_path}")

df_cust = pd.read_csv(customers_path)
df_book = pd.read_csv(bookings_path)

print(f"\nLoaded: {len(df_cust)} customers, {len(df_book)} bookings")

# Chuẩn hóa tên cột tier
tier_col_cust = 'tier' if 'tier' in df_cust.columns else 'tier_code'
tier_col_book = 'tier' if 'tier' in df_book.columns else 'tier_code'

# Đảm bảo tier là string hoa
df_cust[tier_col_cust] = df_cust[tier_col_cust].astype(str).str.upper().str.strip()
df_book[tier_col_book] = df_book[tier_col_book].astype(str).str.upper().str.strip()

# Lọc tier hợp lệ
df_cust = df_cust[df_cust[tier_col_cust].isin(TIER_ORDER)]
df_book = df_book[df_book[tier_col_book].isin(TIER_ORDER)]

# ── 1. Thống kê mô tả ─────────────────────────────────────────────────────────
print("\n[1/5] Thống kê mô tả theo tier...")

numeric_cols = ['lifetime_spend', 'wash_count', 'total_bookings',
                'cancel_rate_pct', 'avg_service_amount']
available = [c for c in numeric_cols if c in df_cust.columns]

summary_rows = []
for tier in TIER_ORDER:
    sub = df_cust[df_cust[tier_col_cust] == tier]
    if sub.empty:
        continue
    row = {'tier': tier, 'count': len(sub)}
    for col in available:
        row[f'{col}_mean']   = round(sub[col].mean(), 2)
        row[f'{col}_median'] = round(sub[col].median(), 2)
        row[f'{col}_std']    = round(sub[col].std(), 2)
        row[f'{col}_min']    = round(sub[col].min(), 2)
        row[f'{col}_max']    = round(sub[col].max(), 2)
    summary_rows.append(row)

df_summary = pd.DataFrame(summary_rows)
df_summary.to_csv(os.path.join(RESULTS_DIR, '01_summary_stats.csv'), index=False)
print(df_summary[['tier', 'count'] +
      [f'lifetime_spend_{s}' for s in ['mean','median','std'] if f'lifetime_spend_{s}' in df_summary.columns]
      ].to_string(index=False))

# ── 2. Biểu đồ: Tier distribution ────────────────────────────────────────────
print("\n[2/5] Vẽ biểu đồ tier distribution...")

tier_counts = df_cust[tier_col_cust].value_counts().reindex(TIER_ORDER).fillna(0)
colors = [TIER_COLORS[t] for t in TIER_ORDER]

fig, axes = plt.subplots(1, 2, figsize=(12, 5))
fig.suptitle('Phân bổ Tier Khách Hàng', fontsize=14, fontweight='bold')

# Bar chart
axes[0].bar(TIER_ORDER, tier_counts.values, color=colors, edgecolor='white', linewidth=1.5)
axes[0].set_title('Số lượng theo Tier')
axes[0].set_xlabel('Tier')
axes[0].set_ylabel('Số khách hàng')
for i, v in enumerate(tier_counts.values):
    axes[0].text(i, v + 0.5, str(int(v)), ha='center', fontweight='bold')

# Pie chart
pct_labels = [f'{t}\n({int(c)})' for t, c in zip(TIER_ORDER, tier_counts.values)]
axes[1].pie(tier_counts.values, labels=pct_labels, colors=colors,
            autopct='%1.1f%%', startangle=90,
            wedgeprops={'edgecolor': 'white', 'linewidth': 1.5})
axes[1].set_title('Tỉ lệ phần trăm')

plt.tight_layout()
plt.savefig(os.path.join(RESULTS_DIR, '01_tier_distribution.png'), bbox_inches='tight')
plt.close()
print("    Đã lưu 01_tier_distribution.png")

# ── 3. Biểu đồ: Spending by tier ─────────────────────────────────────────────
print("\n[3/5] Vẽ biểu đồ spending by tier...")

if 'lifetime_spend' in df_cust.columns:
    fig, axes = plt.subplots(1, 2, figsize=(13, 5))
    fig.suptitle('Chi Tiêu Theo Tier', fontsize=14, fontweight='bold')

    # Box plot
    data_by_tier = [df_cust[df_cust[tier_col_cust] == t]['lifetime_spend'].dropna().values
                    for t in TIER_ORDER]
    bp = axes[0].boxplot(data_by_tier, tick_labels=TIER_ORDER, patch_artist=True)
    for patch, color in zip(bp['boxes'], colors):
        patch.set_facecolor(color)
        patch.set_alpha(0.8)
    axes[0].set_title('Phân phối Lifetime Spend')
    axes[0].set_xlabel('Tier')
    axes[0].set_ylabel('Tổng chi tiêu (đ)')
    axes[0].yaxis.set_major_formatter(mticker.FuncFormatter(lambda x, _: f'{x/1000:.0f}k'))

    # Mean spend bar
    means = [df_cust[df_cust[tier_col_cust] == t]['lifetime_spend'].mean() for t in TIER_ORDER]
    bars = axes[1].bar(TIER_ORDER, means, color=colors, edgecolor='white', linewidth=1.5)
    axes[1].set_title('Trung bình Lifetime Spend')
    axes[1].set_xlabel('Tier')
    axes[1].set_ylabel('Trung bình chi tiêu (đ)')
    axes[1].yaxis.set_major_formatter(mticker.FuncFormatter(lambda x, _: f'{x/1000:.0f}k'))
    for bar, val in zip(bars, means):
        axes[1].text(bar.get_x() + bar.get_width()/2, bar.get_height() + 1000,
                     f'{val/1000:.0f}k', ha='center', fontweight='bold')

    plt.tight_layout()
    plt.savefig(os.path.join(RESULTS_DIR, '01_spending_by_tier.png'), bbox_inches='tight')
    plt.close()
    print("    Đã lưu 01_spending_by_tier.png")

# ── 4. Biểu đồ: Booking frequency ────────────────────────────────────────────
print("\n[4/5] Vẽ biểu đồ booking frequency...")

if 'wash_count' in df_cust.columns:
    fig, axes = plt.subplots(1, 2, figsize=(13, 5))
    fig.suptitle('Tần Suất Rửa Xe Theo Tier', fontsize=14, fontweight='bold')

    # Histogram mỗi tier
    for tier, color in TIER_COLORS.items():
        sub = df_cust[df_cust[tier_col_cust] == tier]['wash_count'].dropna()
        if not sub.empty:
            axes[0].hist(sub, bins=15, alpha=0.6, label=tier, color=color)
    axes[0].set_title('Phân phối số lần rửa xe')
    axes[0].set_xlabel('Số lần rửa')
    axes[0].set_ylabel('Số khách hàng')
    axes[0].legend()

    # Mean wash count bar
    means_wash = [df_cust[df_cust[tier_col_cust] == t]['wash_count'].mean() for t in TIER_ORDER]
    bars2 = axes[1].bar(TIER_ORDER, means_wash, color=colors, edgecolor='white', linewidth=1.5)
    axes[1].set_title('Trung bình số lần rửa xe')
    axes[1].set_xlabel('Tier')
    axes[1].set_ylabel('Số lần')
    for bar, val in zip(bars2, means_wash):
        axes[1].text(bar.get_x() + bar.get_width()/2, bar.get_height() + 0.1,
                     f'{val:.1f}', ha='center', fontweight='bold')

    plt.tight_layout()
    plt.savefig(os.path.join(RESULTS_DIR, '01_booking_frequency.png'), bbox_inches='tight')
    plt.close()
    print("    Đã lưu 01_booking_frequency.png")

# ── 5. Biểu đồ: Cancel rate by tier ──────────────────────────────────────────
print("\n[5/5] Vẽ biểu đồ cancel rate by tier...")

if 'cancel_rate_pct' in df_cust.columns:
    means_cancel = [df_cust[df_cust[tier_col_cust] == t]['cancel_rate_pct'].mean()
                    for t in TIER_ORDER]

    fig, ax = plt.subplots(figsize=(8, 5))
    bars3 = ax.bar(TIER_ORDER, means_cancel, color=colors, edgecolor='white', linewidth=1.5)
    ax.set_title('Tỉ Lệ Hủy Đặt Lịch Theo Tier', fontweight='bold')
    ax.set_xlabel('Tier')
    ax.set_ylabel('Cancel rate (%)')
    ax.set_ylim(0, max(means_cancel) * 1.3 if max(means_cancel) > 0 else 30)
    for bar, val in zip(bars3, means_cancel):
        ax.text(bar.get_x() + bar.get_width()/2, bar.get_height() + 0.3,
                f'{val:.1f}%', ha='center', fontweight='bold')
    ax.axhline(y=np.mean([v for v in means_cancel if v > 0]),
               color='red', linestyle='--', alpha=0.6, label='Trung bình')
    ax.legend()

    plt.tight_layout()
    plt.savefig(os.path.join(RESULTS_DIR, '01_cancel_rate_by_tier.png'), bbox_inches='tight')
    plt.close()
    print("    Đã lưu 01_cancel_rate_by_tier.png")

# ── Tóm tắt ───────────────────────────────────────────────────────────────────
print("\n" + "=" * 55)
print("  KẾT QUẢ")
print("=" * 55)
print(f"Tổng customers phân tích: {len(df_cust)}")
print(f"Tổng bookings phân tích:  {len(df_book)}")
print("\nPhân bổ tier:")
for tier in TIER_ORDER:
    cnt = (df_cust[tier_col_cust] == tier).sum()
    pct = cnt / len(df_cust) * 100
    print(f"  {tier:10s}: {cnt:4d} ({pct:.1f}%)")

if 'lifetime_spend' in df_cust.columns:
    print("\nLifetime spend trung bình:")
    for tier in TIER_ORDER:
        sub = df_cust[df_cust[tier_col_cust] == tier]
        if not sub.empty:
            print(f"  {tier:10s}: {sub['lifetime_spend'].mean():>10,.0f} đ")

print(f"\nFiles output → data/results/")
print("  01_summary_stats.csv")
print("  01_tier_distribution.png")
print("  01_spending_by_tier.png")
print("  01_booking_frequency.png")
print("  01_cancel_rate_by_tier.png")
print("\nHoàn tất EDA!")