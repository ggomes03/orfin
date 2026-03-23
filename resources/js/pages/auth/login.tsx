import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
};

export default function Login({
    status,
    canResetPassword,
    canRegister,
}: Props) {
    return (
        <>
            <Head title="Log in" />
            <div className="grid min-h-svh grid-cols-1 md:grid-cols-[40%_60%]">
                <aside className="relative hidden overflow-hidden md:block">
                    <div
                        className="absolute inset-0"
                        style={{
                            backgroundImage:
                                'radial-gradient(circle at 20% 18%, rgba(190, 245, 205, 0.42) 0%, rgba(190, 245, 205, 0) 34%), radial-gradient(circle at 82% 15%, rgba(142, 218, 165, 0.36) 0%, rgba(142, 218, 165, 0) 31%), radial-gradient(circle at 72% 82%, rgba(17, 87, 40, 0.58) 0%, rgba(17, 87, 40, 0) 42%), linear-gradient(170deg, #66b983 0%, #2a8a49 48%, #114323 100%)',
                        }}
                    />
                    <div className="absolute -left-16 top-12 h-80 w-80 rounded-full bg-white/20 blur-[110px]" />
                    <div className="absolute -right-20 bottom-6 h-80 w-80 rounded-full bg-[#0c3118]/45 blur-[120px]" />
                    <div
                        className="absolute inset-0 opacity-20"
                        style={{
                            backgroundImage:
                                'radial-gradient(rgba(255, 255, 255, 0.3) 0.75px, transparent 0.75px)',
                            backgroundSize: '24px 24px',
                        }}
                    />
                    <div
                        className="absolute inset-0 opacity-[0.08] mix-blend-soft-light"
                        style={{
                            backgroundImage:
                                'repeating-linear-gradient(0deg, rgba(255, 255, 255, 0.5) 0px, rgba(255, 255, 255, 0.5) 1px, transparent 1px, transparent 3px)',
                        }}
                    />
                </aside>

                <section className="flex items-center justify-center bg-[#f2f5f2] px-6 py-10 sm:px-10">
                    <div className="w-full max-w-md rounded-3xl border border-[#d8e2d9] bg-white p-7 shadow-[0_28px_60px_-34px_rgba(15,38,23,0.42)] sm:p-9">
                        <div className="space-y-2 text-center">
                            <h1 className="text-3xl font-semibold tracking-tight text-[#112919] sm:text-[2rem]">
                                Log in to your account
                            </h1>
                            <p className="text-sm text-[#365140]">
                                Enter your email and password below to log in
                            </p>
                        </div>

                        <Form
                            {...store.form()}
                            resetOnSuccess={['password']}
                            className="mt-6 flex flex-col gap-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-6">
                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor="email"
                                                className="text-[#183523]"
                                            >
                                                Email address
                                            </Label>
                                            <Input
                                                id="email"
                                                type="email"
                                                name="email"
                                                required
                                                autoFocus
                                                tabIndex={1}
                                                autoComplete="email"
                                                placeholder="email@example.com"
                                                className="h-11 border-[#c7d8ca] bg-[rgba(255,255,255,0.72)] text-[#122b1a] placeholder:text-[#587261] shadow-[inset_0_1px_0_rgba(255,255,255,0.7)] focus-visible:border-[#227f3e] focus-visible:ring-[#227f3e]/28"
                                            />
                                            <InputError message={errors.email} />
                                        </div>

                                        <div className="grid gap-2">
                                            <div className="flex items-center">
                                                <Label
                                                    htmlFor="password"
                                                    className="text-[#183523]"
                                                >
                                                    Password
                                                </Label>
                                                {canResetPassword && (
                                                    <TextLink
                                                        href={request()}
                                                        className="ml-auto text-sm text-[#2e5840] decoration-[#4d7b60] hover:text-[#173625]"
                                                        tabIndex={5}
                                                    >
                                                        Forgot password?
                                                    </TextLink>
                                                )}
                                            </div>
                                            <PasswordInput
                                                id="password"
                                                name="password"
                                                required
                                                tabIndex={2}
                                                autoComplete="current-password"
                                                placeholder="Password"
                                                className="h-11 border-[#c7d8ca] bg-[rgba(255,255,255,0.72)] text-[#122b1a] placeholder:text-[#587261] shadow-[inset_0_1px_0_rgba(255,255,255,0.7)] focus-visible:border-[#227f3e] focus-visible:ring-[#227f3e]/28"
                                            />
                                            <InputError
                                                message={errors.password}
                                            />
                                        </div>

                                        <div className="flex items-center space-x-3">
                                            <Checkbox
                                                id="remember"
                                                name="remember"
                                                tabIndex={3}
                                                className="border-[#8ea997] bg-white data-[state=checked]:border-[#227f3e] data-[state=checked]:bg-[#227f3e]"
                                            />
                                            <Label
                                                htmlFor="remember"
                                                className="text-[#355443]"
                                            >
                                                Remember me
                                            </Label>
                                        </div>

                                        <Button
                                            type="submit"
                                            className="mt-2 h-11 w-full bg-[#175f2f] text-white shadow-[0_16px_30px_-16px_rgba(15,68,37,0.75)] hover:bg-[#0f4e25] focus-visible:ring-[#227f3e]/40"
                                            tabIndex={4}
                                            disabled={processing}
                                            data-test="login-button"
                                        >
                                            {processing && <Spinner />}
                                            Log in
                                        </Button>
                                    </div>

                                    {canRegister && (
                                        <div className="text-center text-sm text-[#365443]">
                                            Don't have an account?{' '}
                                            <TextLink
                                                href={register()}
                                                className="text-[#123720] decoration-[#3f6e53] hover:text-[#0c2918]"
                                                tabIndex={5}
                                            >
                                                Sign up
                                            </TextLink>
                                        </div>
                                    )}
                                </>
                            )}
                        </Form>

                        {status && (
                            <div className="mt-4 text-center text-sm font-medium text-[#1e6d37]">
                                {status}
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </>
    );
}
