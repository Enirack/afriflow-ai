import { Injectable, signal } from '@angular/core';

/**
 * Carries a message across a hard navigation (e.g. a forced redirect to
 * /login on session expiry) since the component that should show it isn't
 * mounted yet when the message is produced.
 */
@Injectable({ providedIn: 'root' })
export class NotificationService {
  private readonly pendingMessage = signal<string | null>(null);

  show(message: string): void {
    this.pendingMessage.set(message);
  }

  /** Reads and clears the pending message, if any. */
  consume(): string | null {
    const message = this.pendingMessage();
    this.pendingMessage.set(null);

    return message;
  }
}
