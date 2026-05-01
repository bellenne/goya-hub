export const calculateAttributePointBalance = (values = {}, items = [], basePoints = 0) => {
    const balance = {
        base: Number(basePoints ?? 0),
        gained: 0,
        spent: 0,
        available: 0,
    };

    items.forEach((item) => {
        const baseline = Number(item.default ?? 0);
        const value = Number(values[item.key] ?? baseline);
        const delta = value - baseline;

        if (delta > 0) {
            balance.spent += delta;
        } else if (delta < 0) {
            balance.gained += Math.abs(delta);
        }
    });

    balance.available = balance.base + balance.gained - balance.spent;

    return balance;
};

export const attributePointDelta = (values = {}, item = {}) => {
    const baseline = Number(item.default ?? 0);
    const value = Number(values[item.key] ?? baseline);

    return value - baseline;
};
