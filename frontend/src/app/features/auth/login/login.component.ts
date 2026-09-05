import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/auth/auth.service';
import { NotificationService } from '../../../core/notifications/notification.service';

@Component({
  selector: 'app-login',
  imports: [ReactiveFormsModule, RouterLink],
  template: `
    <div class="auth-page">
      <form class="card auth-card" [formGroup]="form" (ngSubmit)="submit()">
        <div class="auth-brand">
          <span class="brand-mark">AF</span>
          <span>AfriFlow AI</span>
        </div>
        <h1>Connexion</h1>
        <p class="subtitle">Accédez au tableau de bord de votre entreprise.</p>

        @if (sessionMessage()) {
          <div class="alert-info">{{ sessionMessage() }}</div>
        }

        @if (error()) {
          <div class="alert-error">{{ error() }}</div>
        }

        <div class="form-field">
          <label for="email">Email</label>
          <input id="email" type="email" formControlName="email" autocomplete="email" />
        </div>

        <div class="form-field">
          <label for="password">Mot de passe</label>
          <input
            id="password"
            type="password"
            formControlName="password"
            autocomplete="current-password"
          />
        </div>

        <button class="btn btn-primary auth-submit" type="submit" [disabled]="form.invalid || loading()">
          {{ loading() ? 'Connexion...' : 'Se connecter' }}
        </button>

        <p class="switch-link">
          Pas encore de compte ? <a routerLink="/register">Créer un compte</a>
        </p>
      </form>
    </div>
  `,
  styles: [
    `
      .auth-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--color-bg);
        padding: 24px;
      }

      .auth-card {
        width: 100%;
        max-width: 380px;
      }

      .auth-brand {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        margin-bottom: 20px;
      }

      .brand-mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 7px;
        background: var(--color-accent);
        color: #3a2600;
        font-size: 12px;
      }

      .subtitle {
        color: var(--color-text-muted);
        margin: 0 0 20px;
        font-size: 13.5px;
      }

      .auth-submit {
        width: 100%;
        justify-content: center;
        margin-top: 4px;
      }

      .switch-link {
        text-align: center;
        margin: 18px 0 0;
        font-size: 13.5px;
        color: var(--color-text-muted);
      }
    `,
  ],
})
export class LoginComponent {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly notifications = inject(NotificationService);

  protected readonly loading = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly sessionMessage = signal(this.notifications.consume());

  protected readonly form = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
  });

  submit(): void {
    if (this.form.invalid) {
      return;
    }

    this.loading.set(true);
    this.error.set(null);

    this.auth.login(this.form.getRawValue()).subscribe({
      next: () => {
        this.loading.set(false);
        this.router.navigateByUrl('/dashboard');
      },
      error: () => {
        this.loading.set(false);
        this.error.set('Email ou mot de passe incorrect.');
      },
    });
  }
}
