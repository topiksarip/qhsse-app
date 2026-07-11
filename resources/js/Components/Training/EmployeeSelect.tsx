import { Employee } from '@/types';
import { forwardRef, SelectHTMLAttributes } from 'react';

interface EmployeeSelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
    employees: Employee[];
    error?: string;
    label?: string;
    required?: boolean;
}

const EmployeeSelect = forwardRef<HTMLSelectElement, EmployeeSelectProps>(
    ({ employees, error, label = 'Karyawan', required = false, className = '', ...props }, ref) => {
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
                    <option value="">— Pilih Karyawan —</option>
                    
                    {employees.map(employee => (
                        <option key={employee.id} value={employee.id}>
                            {employee.name} ({employee.employee_number})
                            {employee.site && ` - ${employee.site.name}`}
                        </option>
                    ))}
                </select>
                
                {error && (
                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                        {error}
                    </p>
                )}
                
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Cari berdasarkan nama atau NIK
                </p>
            </div>
        );
    }
);

EmployeeSelect.displayName = 'EmployeeSelect';

export default EmployeeSelect;
