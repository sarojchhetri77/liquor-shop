const amount = new Intl.NumberFormat('en-IN', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
});

export function formatMoney(value: number | string): string {
    const numeric = typeof value === 'string' ? parseFloat(value) : value;

    return `Rs. ${amount.format(Number.isFinite(numeric) ? numeric : 0)}`;
}
