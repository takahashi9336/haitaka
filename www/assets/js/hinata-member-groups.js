/**
 * メンバー一覧のグルーピング用ユーティリティ
 * 現役は期別（1期生〜5期生、ポカ）、卒業生は下にまとめる
 */
const HinataMemberGroups = (function () {
    const POKA_MEMBER_ID = 99;

    function getGenLabel(gen) {
        if (gen === 'poka') return 'ポカ';
        if (gen === 0 || gen === '0') return '期別なし';
        return gen + '期生';
    }

    /**
     * メンバーを現役（期別）と卒業生にグルーピング
     * @param {Array} members - メンバー配列
     * @returns {{ active: Object, graduates: Array, order: Array }}
     */
    function group(members) {
        const active = {};
        const graduates = [];

        members.forEach(function (m) {
            if (!m.is_active) {
                graduates.push(m);
                return;
            }
            const gen = (m.id === POKA_MEMBER_ID) ? 'poka' : (m.generation || 0);
            if (!active[gen]) active[gen] = [];
            active[gen].push(m);
        });

        const regularKeys = Object.keys(active).filter(function (k) { return k !== 'poka'; }).sort(function (a, b) { return Number(a) - Number(b); });
        const order = active['poka'] ? regularKeys.concat(['poka']) : regularKeys;

        graduates.sort(function (a, b) {
            const ga = a.generation || 0;
            const gb = b.generation || 0;
            if (ga !== gb) return ga - gb;
            return (a.kana || a.name || '').localeCompare(b.kana || b.name || '', 'ja');
        });

        return { active: active, graduates: graduates, order: order };
    }

    return {
        POKA_MEMBER_ID: POKA_MEMBER_ID,
        group: group,
        getGenLabel: getGenLabel
    };
})();
