import { TrainingProgram } from '@/types';
import { forwardRef, SelectHTMLAttributes } from 'react';

interface ProgramSelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
    programs: TrainingProgram[];
    error?: string;
    label?: string;
    required?: boolean;
}

const ProgramSelect = forwardRef<HTMLSelectElement, ProgramSelectProps>(
    ({ programs, error, label = 'Program Pelatihan', required = false, className = '', ...props }, ref) => {
        return (
            <div className="w-full">
                {label && (
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {label}
                        {required && <span className="text-red-500 ml-1">*</span>}
                    </label>
                )}
                
                <select
                    ref={ref}
                    className={`
                        w-full rounded-md border-gray-300 dark:border-gray-600
                        bg-white dark:bg-gray-800
                        text-gray-900 dark:text-gray-100
                        shadow-sm focus:border-indigo-500 focus:ring-indigo-500
                        disabled:bg-gray-100 dark:disabled:bg-gray-700
                        disabled:text-gray-500 dark:disabled:text-gray-400
                        ${error ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''}
                        ${className}
                    `}
                    {...props}
                >
                    <option value="">— Pilih Program —</option>
                    
                    {/* Group by category */}
                    {programs.length > 0 && (
                        <>
                            {/* Safety Programs */}
                            {programs.some(p => p.category === 'safety') && (
                                <optgroup label="Safety">
                                    {programs
                                        .filter(p => p.category === 'safety')
                                        .map(program => (
                                            <option key={program.id} value={program.id}>
                                                {program.code} - {program.name}
                                                {program.is_certification && ' (Sertifikasi)'}
                                            </option>
                                        ))}
                                </optgroup>
                            )}
                            
                            {/* Technical Programs */}
                            {programs.some(p => p.category === 'technical') && (
                                <optgroup label="Technical">
                                    {programs
                                        .filter(p => p.category === 'technical')
                                        .map(program => (
                                            <option key={program.id} value={program.id}>
                                                {program.code} - {program.name}
                                                {program.is_certification && ' (Sertifikasi)'}
                                            </option>
                                        ))}
                                </optgroup>
                            )}
                            
                            {/* Other categories */}
                            {programs.some(p => !['safety', 'technical'].includes(p.category)) && (
                                <optgroup label="Lainnya">
                                    {programs
                                        .filter(p => !['safety', 'technical'].includes(p.category))
                                        .map(program => (
                                            <option key={program.id} value={program.id}>
                                                {program.code} - {program.name}
                                                {program.is_certification && ' (Sertifikasi)'}
                                            </option>
                                        ))}
                                </optgroup>
                            )}
                        </>
                    )}
                </select>
                
                {error && (
                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                        {error}
                    </p>
                )}
                
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Hanya program aktif yang ditampilkan
                </p>
            </div>
        );
    }
);

ProgramSelect.displayName = 'ProgramSelect';

export default ProgramSelect;
