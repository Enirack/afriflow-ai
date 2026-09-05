import { Component, ElementRef, ViewChild, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../core/auth/auth.service';
import { CopilotService } from '../../core/services/copilot.service';

interface ChatMessage {
  role: 'user' | 'assistant';
  text: string;
}

@Component({
  selector: 'app-copilot-widget',
  imports: [FormsModule],
  template: `
    @if (open()) {
      <div class="panel card">
        <div class="panel-header">
          <span class="brand-mark">🤖</span>
          <div>
            <div class="title">AfriFlow AI</div>
            <div class="subtitle">Votre copilote business</div>
          </div>
          <button type="button" class="close-btn" (click)="open.set(false)" aria-label="Fermer">✕</button>
        </div>

        <div class="messages" #messagesEl>
          @if (messages().length === 0) {
            <div class="greeting">
              Bonjour {{ auth.currentUser()?.fullName }} 👋<br />
              Posez-moi une question sur votre activité : ventes, clients, dépenses...
            </div>
          }
          @for (message of messages(); track $index) {
            <div class="message" [class.from-user]="message.role === 'user'">
              {{ message.text }}
            </div>
          }
          @if (loading()) {
            <div class="message from-assistant loading">...</div>
          }
        </div>

        <form class="input-row" (ngSubmit)="submit()">
          <input
            type="text"
            placeholder="Posez-moi une question..."
            [(ngModel)]="question"
            name="question"
            [disabled]="loading()"
          />
          <button class="btn btn-primary" type="submit" [disabled]="loading() || !question.trim()">
            Envoyer
          </button>
        </form>
      </div>
    }

    <button type="button" class="fab" (click)="open.set(!open())" aria-label="Ouvrir le copilote">
      🤖
    </button>
  `,
  styles: [
    `
      :host {
        position: fixed;
        right: 24px;
        bottom: 24px;
        z-index: 100;
      }

      .fab {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        border: none;
        background: var(--color-primary);
        color: white;
        font-size: 24px;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
      }

      .panel {
        position: absolute;
        bottom: 68px;
        right: 0;
        width: 340px;
        height: 440px;
        display: flex;
        flex-direction: column;
        padding: 0;
        overflow: hidden;
      }

      .panel-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--color-border);
      }

      .brand-mark {
        font-size: 20px;
      }

      .title {
        font-weight: 700;
        font-size: 14px;
      }

      .subtitle {
        font-size: 12px;
        color: var(--color-text-muted);
      }

      .close-btn {
        margin-left: auto;
        border: none;
        background: none;
        cursor: pointer;
        color: var(--color-text-muted);
        font-size: 14px;
      }

      .messages {
        flex: 1;
        overflow-y: auto;
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 10px;
      }

      .greeting {
        color: var(--color-text-muted);
        font-size: 13.5px;
        line-height: 1.5;
      }

      .message {
        background: var(--color-bg);
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 13.5px;
        max-width: 85%;
        white-space: pre-wrap;
      }

      .message.from-user {
        align-self: flex-end;
        background: var(--color-primary);
        color: white;
      }

      .message.loading {
        color: var(--color-text-muted);
      }

      .input-row {
        display: flex;
        gap: 8px;
        padding: 12px;
        border-top: 1px solid var(--color-border);
      }

      .input-row input {
        flex: 1;
        padding: 8px 10px;
        border: 1px solid var(--color-border);
        border-radius: 8px;
        font-size: 13.5px;
      }
    `,
  ],
})
export class CopilotWidgetComponent {
  private readonly copilotService = inject(CopilotService);
  protected readonly auth = inject(AuthService);

  @ViewChild('messagesEl') private messagesEl?: ElementRef<HTMLDivElement>;

  protected readonly open = signal(false);
  protected readonly messages = signal<ChatMessage[]>([]);
  protected readonly loading = signal(false);
  protected question = '';

  submit(): void {
    const question = this.question.trim();
    if (!question || this.loading()) {
      return;
    }

    this.messages.update((m) => [...m, { role: 'user', text: question }]);
    this.question = '';
    this.loading.set(true);

    this.copilotService.ask(question).subscribe({
      next: ({ answer }) => {
        this.messages.update((m) => [...m, { role: 'assistant', text: answer }]);
        this.loading.set(false);
        this.scrollToBottom();
      },
      error: () => {
        this.messages.update((m) => [
          ...m,
          { role: 'assistant', text: "Désolé, une erreur est survenue. Réessayez dans un instant." },
        ]);
        this.loading.set(false);
        this.scrollToBottom();
      },
    });
  }

  private scrollToBottom(): void {
    queueMicrotask(() => {
      const el = this.messagesEl?.nativeElement;
      if (el) {
        el.scrollTop = el.scrollHeight;
      }
    });
  }
}
