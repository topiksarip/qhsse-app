import { RiskMatrixLevel, Severity } from '@/types';

const colorMap: Record<string, string> = {
    red: 'bg-red-200 hover:bg-red-300 border-red-400 dark:bg-red-900/60 dark:hover:bg-red-900 dark:border-red-600',
    orange: 'bg-orange-200 hover:bg-orange-300 border-orange-400 dark:bg-orange-900/60 dark:hover:bg-orange-900 dark:border-orange-600',
    yellow: 'bg-yellow-200 hover:bg-yellow-300 border-yellow-400 dark:bg-yellow-900/60 dark:hover:bg-yellow-900 dark:border-yellow-600',
    green: 'bg-green-200 hover:bg-green-300 border-green-400 dark:bg-green-900/60 dark:hover:bg-green-900 dark:border-green-600',
    gray: 'bg-gray-200 hover:bg-gray-300 border-gray-400',
};

const probabilityLabels: Record<number, string> = {
    1: 'P1',
    2: 'P2',
    3: 'P3',
    4: 'P4',
    5: 'P5',
};

interface Props {
    severities: Severity[];
    matrixLevels: RiskMatrixLevel[];
    selectedSeverityId?: number | null;
    selectedProbabilityId?: number | null;
    onSelect?: (severityId: number, probabilityId: number, riskLevelId: number) => void;
    caption?: string;
}

export default function RiskMatrixGrid({
    severities,
    matrixLevels,
    selectedSeverityId,
    selectedProbabilityId,
    onSelect,
    caption,
}: Props) {
    // Rows = severities ordered descending (CRITICAL first); cols = probabilities 1..5.
    const orderedSeverities = [...severities].sort((a, b) => b.level - a.level);
    const probabilities = [1, 2, 3, 4, 5];

    function cellFor(severityLevel: number, probability: number): RiskMatrixLevel | undefined {
        return matrixLevels.find((m) => m.consequence === severityLevel && m.likelihood === probability);
    }

    return (
        <div className="overflow-x-auto">
            <table className="border-collapse text-xs">
                <thead>
                    <tr>
                        <th className="p-1" />
                        {probabilities.map((p) => (
                            <th key={p} className="p-1 text-center font-medium text-gray-600 dark:text-gray-300">
                                {probabilityLabels[p]}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {orderedSeverities.map((s) => (
                        <tr key={s.id}>
                            <th className="p-1 text-right font-medium text-gray-600 dark:text-gray-300">S{s.level}</th>
                            {probabilities.map((p) => {
                                const cell = cellFor(s.level, p);
                                if (!cell) return <td key={p} className="border border-gray-200 p-2 dark:border-gray-700" />;
                                const isSelected = selectedSeverityId === s.id && selectedProbabilityId === p;
                                const cls = colorMap[cell.color] ?? colorMap.gray;
                                return (
                                    <td
                                        key={p}
                                        className={`border p-2 text-center ${cls} ${isSelected ? 'ring-2 ring-offset-2 ring-blue-500 scale-105' : ''} ${onSelect ? 'cursor-pointer' : ''}`}
                                        onClick={() => onSelect && onSelect(s.id, p, cell.id)}
                                        title={`${s.name} × P${p} = ${cell.level}`}
                                    >
                                        {cell.level.toUpperCase()}
                                    </td>
                                );
                            })}
                        </tr>
                    ))}
                </tbody>
            </table>
            {caption && <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">{caption}</p>}
        </div>
    );
}
