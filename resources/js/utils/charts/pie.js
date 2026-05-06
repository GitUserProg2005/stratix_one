export function pieColors(count) {
    const base = [
        'rgba(233,115,88,0.75)',
        'rgba(59,130,246,0.65)',
        'rgba(34,197,94,0.65)',
        'rgba(168,85,247,0.65)',
        'rgba(234,179,8,0.65)',
        'rgba(14,165,233,0.65)',
        'rgba(244,63,94,0.65)',
        'rgba(99,102,241,0.65)',
    ];

    const out = [];
    for (let i = 0; i < count; i += 1) {
        out.push(base[i % base.length]);
    }
    return out;
}

