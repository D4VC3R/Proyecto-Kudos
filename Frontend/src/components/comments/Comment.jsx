import React from 'react';
import { UserCircle } from 'lucide-react';

export const Comment = ({ comment }) => {
  return (
    <div className="bg-slate-50 p-4 rounded-xl border border-slate-100 flex gap-3">
      <div className="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-slate-200 text-slate-500 overflow-hidden">
        {comment.user?.avatar ? (
          <img src={comment.user.avatar} alt={comment.user.name} className="w-full h-full object-cover" />
        ) : (
          <UserCircle size={24} />
        )}
      </div>
      <div className="flex-1">
        <div className="flex justify-between items-start mb-1">
          <span className="font-bold text-sm text-slate-800">{comment.user?.name || 'Usuario'}</span>
          <span className="text-xs text-slate-400">{new Date(comment.created_at).toLocaleDateString()}</span>
        </div>
        <p className="text-sm text-slate-700 mt-1">{comment.content}</p>
      </div>
    </div>
  );
};

