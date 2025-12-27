/**
 * Utility functions for date formatting and time calculations
 */

/**
 * Format time elapsed since a date
 * @param date - The date to calculate elapsed time from
 * @returns A formatted string like "il y a 8h30" or "il y a 2j 5h"
 */
export function formatTimeElapsed(date: Date | string): string {
  const now = new Date();
  const targetDate = typeof date === 'string' ? new Date(date) : date;
  
  // Check if date is valid
  if (isNaN(targetDate.getTime())) {
    return 'Date invalide';
  }

  const diffMs = now.getTime() - targetDate.getTime();
  
  // If date is in the future, return "dans X"
  if (diffMs < 0) {
    const futureDiffMs = Math.abs(diffMs);
    const futureMinutes = Math.floor(futureDiffMs / (1000 * 60));
    const futureHours = Math.floor(futureMinutes / 60);
    const futureDays = Math.floor(futureHours / 24);
    
    if (futureDays > 0) {
      const remainingHours = futureHours % 24;
      if (remainingHours > 0) {
        return `dans ${futureDays}j ${remainingHours}h`;
      }
      return `dans ${futureDays}j`;
    }
    
    if (futureHours > 0) {
      const remainingMinutes = futureMinutes % 60;
      if (remainingMinutes > 0) {
        return `dans ${futureHours}h${remainingMinutes.toString().padStart(2, '0')}`;
      }
      return `dans ${futureHours}h`;
    }
    
    return `dans ${futureMinutes}min`;
  }

  const diffSeconds = Math.floor(diffMs / 1000);
  const diffMinutes = Math.floor(diffSeconds / 60);
  const diffHours = Math.floor(diffMinutes / 60);
  const diffDays = Math.floor(diffHours / 24);

  // Less than 1 minute
  if (diffSeconds < 60) {
    return 'à l\'instant';
  }

  // Less than 1 hour
  if (diffMinutes < 60) {
    return `il y a ${diffMinutes}min`;
  }

  // Less than 24 hours - show hours and minutes
  if (diffHours < 24) {
    const remainingMinutes = diffMinutes % 60;
    if (remainingMinutes > 0) {
      return `il y a ${diffHours}h${remainingMinutes.toString().padStart(2, '0')}`;
    }
    return `il y a ${diffHours}h`;
  }

  // More than 24 hours - show days and hours
  const remainingHours = diffHours % 24;
  if (remainingHours > 0) {
    return `il y a ${diffDays}j ${remainingHours}h`;
  }
  return `il y a ${diffDays}j`;
}

/**
 * Format date with time elapsed information
 * @param date - The date to format
 * @returns A formatted string combining date and elapsed time
 */
export function formatDateWithElapsed(date: Date | string): string {
  const targetDate = typeof date === 'string' ? new Date(date) : date;
  
  if (isNaN(targetDate.getTime())) {
    return 'Date invalide';
  }

  const formattedDate = targetDate.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });

  const elapsed = formatTimeElapsed(date);
  
  return `${formattedDate} (${elapsed})`;
}

